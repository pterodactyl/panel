<?php

namespace Tests\Unit\Http\Middleware\API;

use Mockery as m;
use Pterodactyl\Models\User;
use Pterodactyl\Models\APIKey;
use Pterodactyl\Models\APIPermission;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\API\HasPermissionToResource;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class HasPermissionToResourceTest extends MiddlewareTestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
    }

    /**
     * Test that a non-admin user cannot access admin level routes.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testNonAdminAccessingAdminLevel()
    {
        $model = factory(APIKey::class)->make();
        $this->setRequestAttribute('api_key', $model);
        $this->setRequestUser(factory(User::class)->make(['root_admin' => false]));

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test non-admin accessing non-admin route.
     */
    public function testAccessingAllowedRoute()
    {
        $model = factory(APIKey::class)->make();
        $model->setRelation('permissions', collect([
            factory(APIPermission::class)->make(['permission' => 'user.test-route']),
        ]));
        $this->setRequestAttribute('api_key', $model);
        $this->setRequestUser(factory(User::class)->make(['root_admin' => false]));

        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('api.user.test.route');
        $this->repository->shouldReceive('loadPermissions')->with($model)->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), 'user');
    }

    /**
     * Test admin accessing administrative route.
     */
    public function testAccessingAllowedAdminRoute()
    {
        $model = factory(APIKey::class)->make();
        $model->setRelation('permissions', collect([
            factory(APIPermission::class)->make(['permission' => 'test-route']),
        ]));
        $this->setRequestAttribute('api_key', $model);
        $this->setRequestUser(factory(User::class)->make(['root_admin' => true]));

        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('api.admin.test.route');
        $this->repository->shouldReceive('loadPermissions')->with($model)->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test a user accessing a disallowed route.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAccessingDisallowedRoute()
    {
        $model = factory(APIKey::class)->make();
        $model->setRelation('permissions', collect([
            factory(APIPermission::class)->make(['permission' => 'user.other-route']),
        ]));
        $this->setRequestAttribute('api_key', $model);
        $this->setRequestUser(factory(User::class)->make(['root_admin' => false]));

        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('api.user.test.route');
        $this->repository->shouldReceive('loadPermissions')->with($model)->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions(), 'user');
    }

    /**
     * Return an instance of the middleware with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Http\Middleware\API\HasPermissionToResource
     */
    private function getMiddleware(): HasPermissionToResource
    {
        return new HasPermissionToResource($this->repository);
    }
}
