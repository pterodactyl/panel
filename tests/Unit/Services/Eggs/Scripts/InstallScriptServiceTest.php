<?php

namespace Tests\Unit\Services\Eggs\Scripts;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\Scripts\InstallScriptService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;

class InstallScriptServiceTest extends TestCase
{
    /**
     * @var array
     */
    protected $data = [
        'script_install' => 'test-script',
        'script_is_privileged' => true,
        'script_entry' => '/bin/bash',
        'script_container' => 'ubuntu',
        'copy_script_from' => null,
    ];

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(EggRepositoryInterface::class);
    }

    /**
     * Test that passing a new copy_script_from attribute works properly.
     */
    public function testUpdateWithValidCopyScriptFromAttribute()
    {
        $model = factory(Egg::class)->make(['id' => 123, 'nest_id' => 456]);
        $this->data['copy_script_from'] = 1;

        $this->repository->shouldReceive('isCopyableScript')->with(1, $model->nest_id)->once()->andReturn(true);
        $this->repository->expects('withoutFreshModel->update')->with($model->id, $this->data)->andReturnNull();

        $this->getService()->handle($model, $this->data);
    }

    /**
     * Test that an exception gets raised when the script is not copyable.
     */
    public function testUpdateWithInvalidCopyScriptFromAttribute()
    {
        $this->data['copy_script_from'] = 1;

        $this->expectException(InvalidCopyFromException::class);
        $this->expectExceptionMessage(trans('exceptions.nest.egg.invalid_copy_id'));

        $model = factory(Egg::class)->make(['id' => 123, 'nest_id' => 456]);

        $this->repository->expects('isCopyableScript')->with(1, $model->nest_id)->andReturn(false);
        $this->getService()->handle($model, $this->data);
    }

    /**
     * Test standard functionality.
     */
    public function testUpdateWithoutNewCopyScriptFromAttribute()
    {
        $model = factory(Egg::class)->make(['id' => 123, 'nest_id' => 456]);

        $this->repository->expects('withoutFreshModel->update')->with($model->id, $this->data)->andReturnNull();

        $this->getService()->handle($model, $this->data);
    }

    private function getService()
    {
        return new InstallScriptService($this->repository);
    }
}
