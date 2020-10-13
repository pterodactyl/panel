<?php

namespace Tests\Unit\Services\Eggs\Sharing;

use Mockery as m;
use Carbon\Carbon;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Tests\Assertions\NestedObjectAssertionsTrait;
use Pterodactyl\Services\Eggs\Sharing\EggExporterService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;

class EggExporterServiceTest extends TestCase
{
    use NestedObjectAssertionsTrait;

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

        Carbon::setTestNow(Carbon::now());

        $this->repository = m::mock(EggRepositoryInterface::class);
    }

    /**
     * Test that a JSON structure is returned.
     */
    public function testJsonStructureIsExported()
    {
        $egg = factory(Egg::class)->make([
            'id' => 123,
            'nest_id' => 456,
        ]);
        $egg->variables = collect([$variable = factory(EggVariable::class)->make()]);

        $this->repository->shouldReceive('getWithExportAttributes')->with($egg->id)->once()->andReturn($egg);

        $service = new EggExporterService($this->repository);

        $response = $service->handle($egg->id);
        $this->assertNotEmpty($response);

        $data = json_decode($response);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertObjectHasNestedAttribute('meta.version', $data);
        $this->assertObjectNestedValueEquals('meta.version', 'PTDL_v1', $data);
        $this->assertObjectHasNestedAttribute('author', $data);
        $this->assertObjectNestedValueEquals('author', $egg->author, $data);
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
