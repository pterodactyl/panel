<?php

namespace Tests\Unit\Services\Eggs\Sharing;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\EggVariable;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class EggUpdateImporterServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Illuminate\Http\UploadedFile|\Mockery\Mock
     */
    protected $file;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    protected $variableRepository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->file = m::mock(UploadedFile::class);
        $this->repository = m::mock(EggRepositoryInterface::class);
        $this->variableRepository = m::mock(EggVariableRepositoryInterface::class);

        $this->service = new EggUpdateImporterService($this->connection, $this->repository, $this->variableRepository);
    }

    /**
     * Test that an egg update is handled correctly using an uploaded file.
     */
    public function testEggIsUpdated()
    {
        $egg = factory(Egg::class)->make();
        $variable = factory(EggVariable::class)->make();

        $this->file->shouldReceive('getError')->withNoArgs()->once()->andReturn(UPLOAD_ERR_OK);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'PTDL_v1'],
            'name' => $egg->name,
            'author' => 'newauthor@example.com',
            'variables' => [$variable->toArray()],
        ]));

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('update')->with($egg->id, m::subset([
            'author' => 'newauthor@example.com',
            'name' => $egg->name,
        ]), true, true)->once()->andReturn($egg);

        $this->variableRepository->shouldReceive('withoutFreshModel->updateOrCreate')->with([
            'egg_id' => $egg->id,
            'env_variable' => $variable->env_variable,
        ], collect($variable)->except(['egg_id', 'env_variable'])->toArray())->once()->andReturnNull();

        $this->variableRepository->shouldReceive('setColumns')->with(['id', 'env_variable'])->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([['egg_id', '=', $egg->id]])->once()->andReturn(collect([$variable]));

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($egg->id, $this->file);
        $this->assertTrue(true);
    }

    /**
     * Test that an imported file with less variables than currently existing deletes
     * the un-needed variables from the database.
     */
    public function testVariablesMissingFromImportAreDeleted()
    {
        $egg = factory(Egg::class)->make();
        $variable1 = factory(EggVariable::class)->make();
        $variable2 = factory(EggVariable::class)->make();

        $this->file->shouldReceive('getError')->withNoArgs()->once()->andReturn(UPLOAD_ERR_OK);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'PTDL_v1'],
            'name' => $egg->name,
            'author' => 'newauthor@example.com',
            'variables' => [$variable1->toArray()],
        ]));

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('update')->with($egg->id, m::subset([
            'author' => 'newauthor@example.com',
            'name' => $egg->name,
        ]), true, true)->once()->andReturn($egg);

        $this->variableRepository->shouldReceive('withoutFreshModel->updateOrCreate')->with([
            'egg_id' => $egg->id,
            'env_variable' => $variable1->env_variable,
        ], collect($variable1)->except(['egg_id', 'env_variable'])->toArray())->once()->andReturnNull();

        $this->variableRepository->shouldReceive('setColumns')->with(['id', 'env_variable'])->once()->andReturnSelf()
            ->shouldReceive('findWhere')->with([['egg_id', '=', $egg->id]])->once()->andReturn(collect([$variable1, $variable2]));

        $this->variableRepository->shouldReceive('deleteWhere')->with([
            ['egg_id', '=', $egg->id],
            ['env_variable', '=', $variable2->env_variable],
        ])->once()->andReturn(1);

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($egg->id, $this->file);
        $this->assertTrue(true);
    }

    /**
     * Test that an exception is thrown if the file is invalid.
     */
    public function testExceptionIsThrownIfFileIsInvalid()
    {
        $this->file->shouldReceive('getError')->withNoArgs()->once()->andReturn(UPLOAD_ERR_NO_FILE);
        try {
            $this->service->handle(1234, $this->file);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.importer.file_error'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if the file is not a file.
     */
    public function testExceptionIsThrownIfFileIsNotAFile()
    {
        $this->file->shouldReceive('getError')->withNoArgs()->once()->andReturn(UPLOAD_ERR_OK);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(false);

        try {
            $this->service->handle(1234, $this->file);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.importer.file_error'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if the JSON metadata is invalid.
     */
    public function testExceptionIsThrownIfJsonMetaDataIsInvalid()
    {
        $this->file->shouldReceive('getError')->withNoArgs()->once()->andReturn(UPLOAD_ERR_OK);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'hodor'],
        ]));

        try {
            $this->service->handle(1234, $this->file);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.importer.invalid_json_provided'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if bad JSON is provided.
     */
    public function testExceptionIsThrownIfBadJsonIsProvided()
    {
        $this->file->shouldReceive('getError')->withNoArgs()->once()->andReturn(UPLOAD_ERR_OK);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn('}');

        try {
            $this->service->handle(1234, $this->file);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(BadJsonFormatException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.importer.json_error', [
                'error' => json_last_error_msg(),
            ]), $exception->getMessage());
        }
    }
}
