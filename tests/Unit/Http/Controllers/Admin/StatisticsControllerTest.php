<?php
/**
 * Created by PhpStorm.
 * User: Stan
 * Date: 26-5-2018
 * Time: 21:06.
 */

namespace Tests\Unit\Http\Controllers\Admin;

use Mockery as m;
use Pterodactyl\Models\Node;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Http\Controllers\Admin\StatisticsController;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class StatisticsControllerTest extends ControllerTestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    private $allocationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $databaseRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    private $eggRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    private $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $userRepository;

    public function setUp()
    {
        parent::setUp();

        $this->allocationRepository = m::mock(AllocationRepositoryInterface::class);
        $this->databaseRepository = m::mock(DatabaseRepositoryInterface::class);
        $this->eggRepository = m::mock(EggRepositoryInterface::class);
        $this->nodeRepository = m::mock(NodeRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);
    }

    public function testIndexController()
    {
        $controller = $this->getController();

        $this->serverRepository->shouldReceive('all')->withNoArgs();
        $this->nodeRepository->shouldReceive('all')->withNoArgs()->andReturn(collect([factory(Node::class)->make(), factory(Node::class)->make()]));
        $this->userRepository->shouldReceive('count')->withNoArgs();
        $this->eggRepository->shouldReceive('count')->withNoArgs();
        $this->databaseRepository->shouldReceive('count')->withNoArgs();
        $this->allocationRepository->shouldReceive('count')->withNoArgs();
        $this->serverRepository->shouldReceive('getSuspendedServersCount')->withNoArgs();

        $this->nodeRepository->shouldReceive('getUsageStatsRaw')->twice()->andReturn([
            'memory' => [
                'value' => 1024,
                'max' => 512,
            ],
            'disk' => [
                'value' => 1024,
                'max' => 512,
            ],
        ]);

        $controller->shouldReceive('injectJavascript')->once();

        $response = $controller->index();

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('admin.statistics', $response);
    }

    private function getController()
    {
        return $this->buildMockedController(StatisticsController::class, [$this->allocationRepository,
            $this->databaseRepository,
            $this->eggRepository,
            $this->nodeRepository,
            $this->serverRepository,
            $this->userRepository, ]
        );
    }
}
