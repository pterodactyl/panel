<?php

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Pterodactyl\Models\User;
use Pterodactyl\Models\APIKey;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Services\Api\KeyCreationService;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Controllers\Base\APIController;
use Pterodactyl\Http\Requests\Base\ApiKeyFormRequest;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class APIControllerTest extends ControllerTestCase
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag|\Mockery\Mock
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService|\Mockery\Mock
     */
    protected $keyService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->keyService = m::mock(KeyCreationService::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $model = $this->generateRequestUserModel();

        $this->repository->shouldReceive('findWhere')->with([['user_id', '=', $model->id]])->once()->andReturn(collect(['testkeys']));

        $response = $this->getController()->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.api.index', $response);
        $this->assertViewHasKey('keys', $response);
        $this->assertViewKeyEquals('keys', collect(['testkeys']), $response);
    }

    /**
     * Test the create API view controller.
     *
     * @dataProvider rootAdminDataProvider
     */
    public function testCreateController($admin)
    {
        $this->generateRequestUserModel(['root_admin' => $admin]);

        $response = $this->getController()->create($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.api.new', $response);
        $this->assertViewHasKey('permissions.user', $response);
        $this->assertViewHasKey('permissions.admin', $response);

        if ($admin) {
            $this->assertViewKeyNotEquals('permissions.admin', null, $response);
        } else {
            $this->assertViewKeyEquals('permissions.admin', null, $response);
        }
    }

    /**
     * Test the store functionality for a non-admin user.
     *
     * @dataProvider rootAdminDataProvider
     */
    public function testStoreController($admin)
    {
        $this->setRequestMockClass(ApiKeyFormRequest::class);
        $model = $this->generateRequestUserModel(['root_admin' => $admin]);
        $keyModel = factory(APIKey::class)->make();

        if ($admin) {
            $this->request->shouldReceive('input')->with('admin_permissions', [])->once()->andReturn(['admin.permission']);
        }

        $this->request->shouldReceive('user')->withNoArgs()->andReturn($model);
        $this->request->shouldReceive('input')->with('allowed_ips')->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('memo')->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('permissions', [])->once()->andReturn(['test.permission']);

        $this->keyService->shouldReceive('handle')->with([
            'user_id' => $model->id,
            'allowed_ips' => null,
            'memo' => null,
        ], ['test.permission'], ($admin) ? ['admin.permission'] : [])->once()->andReturn($keyModel);

        $this->alert->shouldReceive('success')->with(trans('base.api.index.keypair_created'))->once()->andReturnSelf();
        $this->alert->shouldReceive('flash')->withNoArgs()->once()->andReturnNull();

        $response = $this->getController()->store($this->request);
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectRouteEquals('account.api', $response);
    }

    /**
     * Test the API key revocation controller.
     */
    public function testRevokeController()
    {
        $model = $this->generateRequestUserModel();

        $this->repository->shouldReceive('deleteWhere')->with([
            ['user_id', '=', $model->id],
            ['token', '=', 'testKey123'],
        ])->once()->andReturn(1);

        $response = $this->getController()->revoke($this->request, 'testKey123');
        $this->assertIsResponse($response);
        $this->assertEmpty($response->getContent());
        $this->assertResponseCodeEquals(204, $response);
    }

    /**
     * Data provider to determine if a user is a root admin.
     *
     * @return array
     */
    public function rootAdminDataProvider()
    {
        return [[0], [1]];
    }

    /**
     * Return an instance of the controller with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Http\Controllers\Base\APIController
     */
    private function getController(): APIController
    {
        return new APIController($this->alert, $this->repository, $this->keyService);
    }
}
