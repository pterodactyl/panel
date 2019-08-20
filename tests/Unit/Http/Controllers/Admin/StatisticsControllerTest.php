<?php
/**
 * Created by PhpStorm.
 * User: Stan
 * Date: 26-5-2018
 * Time: 21:06.
 */

namespace Tests\Unit\Http\Controllers\Admin;

use Mockery as m;
use App\Models\Node;
use Tests\Assertions\ControllerAssertionsTrait;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Http\Controllers\Admin\StatisticsController;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Contracts\Repository\AllocationRepositoryInterface;

class StatisticsControllerTest extends ControllerTestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    private $allocationRepository;

    /**
     * @var \App\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $databaseRepository;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    private $eggRepository;

    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    private $nodeRepository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $serverRepository;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $userRepository;

    public function setUp(): void
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
