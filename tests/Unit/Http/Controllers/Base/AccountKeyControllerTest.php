<?php

namespace Tests\Unit\Http\Controllers\Base;

use Mockery as m;
use Pterodactyl\Models\ApiKey;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Services\Api\KeyCreationService;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Requests\Base\StoreAccountKeyRequest;
use Pterodactyl\Http\Controllers\Base\AccountKeyController;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class AccountKeyControllerTest extends ControllerTestCase
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
        $this->markTestSkipped('Not implemented');

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

        $this->repository->shouldReceive('getAccountKeys')->with($model)->once()->andReturn(collect(['testkeys']));

        $response = $this->getController()->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('base.api.index', $response);
        $this->assertViewHasKey('keys', $response);
        $this->assertViewKeyEquals('keys', collect(['testkeys']), $response);
    }

    /**
     * Test the create API view controller.
     */
    public function testCreateController()
    {
        $this->generateRequestUserModel();

        $response = $this->getController()->create($this->request);
        $this->assertIsViewResponse($response);
    }

    /**
     * Test the store functionality for a user.
     */
    public function testStoreController()
    {
        $this->setRequestMockClass(StoreAccountKeyRequest::class);
        $model = $this->generateRequestUserModel();
        $keyModel = factory(ApiKey::class)->make();

        $this->request->shouldReceive('user')->withNoArgs()->andReturn($model);
        $this->request->shouldReceive('input')->with('allowed_ips')->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('memo')->once()->andReturnNull();

        $this->keyService->shouldReceive('setKeyType')->with(ApiKey::TYPE_ACCOUNT)->once()->andReturnSelf();
        $this->keyService->shouldReceive('handle')->with([
            'user_id' => $model->id,
            'allowed_ips' => null,
            'memo' => null,
        ])->once()->andReturn($keyModel);

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

        $this->repository->shouldReceive('deleteAccountKey')->with($model, 'testIdentifier')->once()->andReturn(1);

        $response = $this->getController()->revoke($this->request, 'testIdentifier');
        $this->assertIsResponse($response);
        $this->assertEmpty($response->getContent());
        $this->assertResponseCodeEquals(204, $response);
    }

    /**
     * Return an instance of the controller with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Http\Controllers\Base\AccountKeyController
     */
    private function getController(): AccountKeyController
    {
        return new AccountKeyController($this->alert, $this->repository, $this->keyService);
    }
}
