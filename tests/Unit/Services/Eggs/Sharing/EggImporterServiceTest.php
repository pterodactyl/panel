<?php

namespace Tests\Unit\Services\Eggs\Sharing;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Tests\Traits\MocksUuids;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\EggVariable;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class EggImporterServiceTest extends TestCase
{
    use MocksUuids;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    protected $eggVariableRepository;

    /**
     * @var \Illuminate\Http\UploadedFile|\Mockery\Mock
     */
    protected $file;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface|\Mockery\Mock
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\Sharing\EggImporterService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->file = m::mock(UploadedFile::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->eggVariableRepository = m::mock(EggVariableRepositoryInterface::class);
        $this->nestRepository = m::mock(NestRepositoryInterface::class);
        $this->repository = m::mock(EggRepositoryInterface::class);

        $this->service = new EggImporterService(
            $this->connection, $this->repository, $this->eggVariableRepository, $this->nestRepository
        );
    }

    /**
     * Test that a service option can be successfully imported.
     */
    public function testEggConfigurationIsImported()
    {
        $egg = factory(Egg::class)->make(['id' => 123]);
        $nest = factory(Nest::class)->make(['id' => 456]);

        $this->file->expects('getError')->andReturn(UPLOAD_ERR_OK);
        $this->file->expects('isFile')->andReturn(true);
        $this->file->expects('getSize')->andReturn(100);

        $this->file->expects('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'PTDL_v1'],
            'name' => $egg->name,
            'author' => $egg->author,
            'variables' => [
                $variable = factory(EggVariable::class)->make(),
            ],
        ]));
        $this->nestRepository->shouldReceive('getWithEggs')->with($nest->id)->once()->andReturn($nest);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with(m::subset([
            'uuid' => $this->getKnownUuid(),
            'nest_id' => $nest->id,
            'name' => $egg->name,
        ]), true, true)->once()->andReturn($egg);

        $this->eggVariableRepository->shouldReceive('create')->with(m::subset([
            'egg_id' => $egg->id,
            'env_variable' => $variable->env_variable,
        ]))->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($this->file, $nest->id);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Egg::class, $response);
        $this->assertSame($egg, $response);
    }

    /**
     * Test that an exception is thrown if the file is invalid.
     */
    public function testExceptionIsThrownIfFileIsInvalid()
    {
        $this->expectException(InvalidFileUploadException::class);
        $this->expectExceptionMessage(
            'The selected file ["test.txt"] was not in a valid format to import. (is_file: true is_valid: true err_code: 4 err: UPLOAD_ERR_NO_FILE)'
        );

        $this->file->expects('getFilename')->andReturns('test.txt');
        $this->file->expects('isFile')->andReturns(true);
        $this->file->expects('isValid')->andReturns(true);
        $this->file->expects('getError')->twice()->andReturns(UPLOAD_ERR_NO_FILE);
        $this->file->expects('getErrorMessage')->andReturns('UPLOAD_ERR_NO_FILE');

        $this->service->handle($this->file, 1234);
    }

    /**
     * Test that an exception is thrown if the file is not a file.
     */
    public function testExceptionIsThrownIfFileIsNotAFile()
    {
        $this->expectException(InvalidFileUploadException::class);
        $this->expectExceptionMessage(
            'The selected file ["test.txt"] was not in a valid format to import. (is_file: false is_valid: true err_code: 4 err: UPLOAD_ERR_NO_FILE)'
        );

        $this->file->expects('getFilename')->andReturns('test.txt');
        $this->file->expects('isFile')->andReturns(false);
        $this->file->expects('isValid')->andReturns(true);
        $this->file->expects('getError')->twice()->andReturns(UPLOAD_ERR_NO_FILE);
        $this->file->expects('getErrorMessage')->andReturns('UPLOAD_ERR_NO_FILE');

        $this->service->handle($this->file, 1234);
    }

    /**
     * Test that an exception is thrown if the JSON metadata is invalid.
     */
    public function testExceptionIsThrownIfJsonMetaDataIsInvalid()
    {
        $this->expectException(InvalidFileUploadException::class);
        $this->expectExceptionMessage(trans('exceptions.nest.importer.invalid_json_provided'));

        $this->file->expects('getError')->andReturn(UPLOAD_ERR_OK);
        $this->file->expects('isFile')->andReturn(true);
        $this->file->expects('getSize')->andReturn(100);

        $this->file->expects('openFile->fread')->with(100)->andReturn(json_encode([
            'meta' => ['version' => 'hodor'],
        ]));

        $this->service->handle($this->file, 1234);
    }

    /**
     * Test that an exception is thrown if bad JSON is provided.
     */
    public function testExceptionIsThrownIfBadJsonIsProvided()
    {
        $this->expectException(BadJsonFormatException::class);
        $this->expectExceptionMessage(trans('exceptions.nest.importer.json_error', [
            'error' => 'Syntax error',
        ]));

        $this->file->expects('getError')->andReturn(UPLOAD_ERR_OK);
        $this->file->expects('isFile')->andReturn(true);
        $this->file->expects('getSize')->andReturn(100);
        $this->file->expects('openFile->fread')->with(100)->andReturn('}');

        $this->service->handle($this->file, 1234);
    }
}
