<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services\Sharing;

use Mockery as m;
use Carbon\Carbon;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Tests\Assertions\NestedObjectAssertionsTrait;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Services\Services\Sharing\ServiceOptionExporterService;

class ServiceOptionExporterServiceTest extends TestCase
{
    use NestedObjectAssertionsTrait;

    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Sharing\ServiceOptionExporterService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
        $this->carbon = new Carbon();
        $this->repository = m::mock(EggRepositoryInterface::class);

        $this->service = new ServiceOptionExporterService($this->carbon, $this->repository);
    }

    /**
     * Test that a JSON structure is returned.
     */
    public function testJsonStructureIsExported()
    {
        $option = factory(Egg::class)->make();
        $option->variables = collect([$variable = factory(EggVariable::class)->make()]);

        $this->repository->shouldReceive('getWithExportAttributes')->with($option->id)->once()->andReturn($option);

        $response = $this->service->handle($option->id);
        $this->assertNotEmpty($response);

        $data = json_decode($response);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertObjectHasNestedAttribute('meta.version', $data);
        $this->assertObjectNestedValueEquals('meta.version', 'PTDL_v1', $data);
        $this->assertObjectHasNestedAttribute('exported_at', $data);
        $this->assertObjectNestedValueEquals('exported_at', Carbon::now()->toIso8601String(), $data);
        $this->assertObjectHasNestedAttribute('scripts.installation.script', $data);
        $this->assertObjectHasNestedAttribute('scripts.installation.container', $data);
        $this->assertObjectHasNestedAttribute('scripts.installation.entrypoint', $data);
        $this->assertObjectHasAttribute('variables', $data);
        $this->assertArrayHasKey('0', $data->variables);
        $this->assertObjectHasAttribute('name', $data->variables[0]);
        $this->assertObjectNestedValueEquals('name', $variable->name, $data->variables[0]);
    }
}
