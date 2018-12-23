<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepository;

class StartupModificationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    private $eggRepository;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService|\Mockery\Mock
     */
    private $environmentService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface|\Mockery\Mock
     */
    private $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService|\Mockery\Mock
     */
    private $validatorService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->daemonServerRepository = m::mock(DaemonServerRepository::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->eggRepository = m::mock(EggRepositoryInterface::class);
        $this->environmentService = m::mock(EnvironmentService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->validatorService = m::mock(VariableValidatorService::class);
    }

    /**
     * Test startup modification as a non-admin user.
     */
    public function testStartupModifiedAsNormalUser()
    {
        $model = factory(Server::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->validatorService->shouldReceive('setUserLevel')->with(User::USER_LEVEL_USER)->once()->andReturnNull();
        $this->validatorService->shouldReceive('handle')->with(123, ['test' => 'abcd1234'])->once()->andReturn(
            collect([(object) ['id' => 1, 'value' => 'stored-value']])
        );

        $this->serverVariableRepository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf();
        $this->serverVariableRepository->shouldReceive('updateOrCreate')->with([
            'server_id' => $model->id,
            'variable_id' => 1,
        ], ['variable_value' => 'stored-value'])->once()->andReturnNull();

        $this->environmentService->shouldReceive('handle')->with($model)->once()->andReturn(['env']);
        $this->daemonServerRepository->shouldReceive('setServer')->with($model)->once()->andReturnSelf();
        $this->daemonServerRepository->shouldReceive('update')->with([
            'build' => ['env|overwrite' => ['env']],
        ])->once()->andReturn(new Response);

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->getService()->handle($model, ['egg_id' => 123, 'environment' => ['test' => 'abcd1234']]);

        $this->assertInstanceOf(Server::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Test startup modification as an admin user.
     */
    public function testStartupModificationAsAdminUser()
    {
        $model = factory(Server::class)->make([
            'egg_id' => 123,
            'image' => 'docker:image',
        ]);

        $eggModel = factory(Egg::class)->make([
            'id' => 456,
            'nest_id' => 12345,
        ]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->validatorService->shouldReceive('setUserLevel')->with(User::USER_LEVEL_ADMIN)->once()->andReturnNull();
        $this->validatorService->shouldReceive('handle')->with(456, ['test' => 'abcd1234'])->once()->andReturn(
            collect([(object) ['id' => 1, 'value' => 'stored-value'], (object) ['id' => 2, 'value' => null]])
        );

        $this->serverVariableRepository->shouldReceive('withoutFreshModel->updateOrCreate')->once()->with([
            'server_id' => $model->id,
            'variable_id' => 1,
        ], ['variable_value' => 'stored-value'])->andReturnNull();

        $this->serverVariableRepository->shouldReceive('withoutFreshModel->updateOrCreate')->once()->with([
            'server_id' => $model->id,
            'variable_id' => 2,
        ], ['variable_value' => ''])->andReturnNull();

        $this->eggRepository->shouldReceive('setColumns->find')->once()->with($eggModel->id)->andReturn($eggModel);

        $this->repository->shouldReceive('update')->with($model->id, m::subset([
            'installed' => 0,
            'nest_id' => $eggModel->nest_id,
            'egg_id' => $eggModel->id,
            'pack_id' => 789,
            'image' => 'docker:image',
        ]))->once()->andReturn($model);
        $this->repository->shouldReceive('getDaemonServiceData')->with($model, true)->once()->andReturn([
            'egg' => 'abcd1234',
            'pack' => 'xyz987',
        ]);

        $this->environmentService->shouldReceive('handle')->with($model)->once()->andReturn(['env']);

        $this->daemonServerRepository->shouldReceive('setServer')->with($model)->once()->andReturnSelf();
        $this->daemonServerRepository->shouldReceive('update')->with([
            'build' => [
                'env|overwrite' => ['env'],
                'image' => $model->image,
            ],
            'service' => [
                'egg' => 'abcd1234',
                'pack' => 'xyz987',
                'skip_scripts' => false,
            ],
        ])->once()->andReturn(new Response);

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $service = $this->getService();
        $service->setUserLevel(User::USER_LEVEL_ADMIN);
        $response = $service->handle($model, [
            'docker_image' => 'docker:image',
            'egg_id' => $eggModel->id,
            'pack_id' => 789,
            'environment' => ['test' => 'abcd1234'],
        ]);

        $this->assertInstanceOf(Server::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\StartupModificationService
     */
    private function getService(): StartupModificationService
    {
        return new StartupModificationService(
            $this->connection,
            $this->daemonServerRepository,
            $this->eggRepository,
            $this->environmentService,
            $this->repository,
            $this->serverVariableRepository,
            $this->validatorService
        );
    }
}
