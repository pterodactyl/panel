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
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Service;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Models\ServiceVariable;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Services\Services\Sharing\ServiceOptionImporterService;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\DuplicateOptionTagException;

class ServiceOptionImporterServiceTest extends TestCase
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
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Sharing\ServiceOptionImporterService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface|\Mockery\Mock
     */
    protected $serviceRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface|\Mockery\Mock
     */
    protected $serviceVariableRepository;

    /**
     * @var \Ramsey\Uuid\Uuid|\Mockery\Mock
     */
    protected $uuid;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->file = m::mock(UploadedFile::class);
        $this->repository = m::mock(ServiceOptionRepositoryInterface::class);
        $this->serviceRepository = m::mock(ServiceRepositoryInterface::class);
        $this->serviceVariableRepository = m::mock(ServiceVariableRepositoryInterface::class);
        $this->uuid = m::mock('overload:' . Uuid::class);

        $this->service = new ServiceOptionImporterService(
            $this->connection, $this->serviceRepository, $this->repository, $this->serviceVariableRepository
        );
    }

    /**
     * Test that a service option can be successfully imported.
     */
    public function testServiceOptionIsImported()
    {
        $option = factory(ServiceOption::class)->make();
        $service = factory(Service::class)->make();
        $service->options = collect([factory(ServiceOption::class)->make()]);

        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'PTDL_v1'],
            'name' => $option->name,
            'tag' => $option->tag,
            'variables' => [
                $variable = factory(ServiceVariable::class)->make(),
            ],
        ]));
        $this->serviceRepository->shouldReceive('getWithOptions')->with($service->id)->once()->andReturn($service);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->uuid->shouldReceive('uuid4->toString')->withNoArgs()->once()->andReturn($option->uuid);
        $this->repository->shouldReceive('create')->with(m::subset([
            'uuid' => $option->uuid,
            'service_id' => $service->id,
            'name' => $option->name,
            'tag' => $option->tag,
        ]), true, true)->once()->andReturn($option);

        $this->serviceVariableRepository->shouldReceive('create')->with(m::subset([
            'option_id' => $option->id,
            'env_variable' => $variable->env_variable,
        ]))->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($this->file, $service->id);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ServiceOption::class, $response);
        $this->assertSame($option, $response);
    }

    /**
     * Test that an exception is thrown if the file is invalid.
     */
    public function testExceptionIsThrownIfFileIsInvalid()
    {
        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(false);
        try {
            $this->service->handle($this->file, 1234);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.service.exporter.import_file_error'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if the file is not a file.
     */
    public function testExceptionIsThrownIfFileIsNotAFile()
    {
        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(false);

        try {
            $this->service->handle($this->file, 1234);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.service.exporter.import_file_error'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if the JSON metadata is invalid.
     */
    public function testExceptionIsThrownIfJsonMetaDataIsInvalid()
    {
        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'hodor'],
        ]));

        try {
            $this->service->handle($this->file, 1234);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.service.exporter.invalid_json_provided'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if a duplicate tag exists.
     */
    public function testExceptionIsThrownIfDuplicateTagExists()
    {
        $option = factory(ServiceOption::class)->make();
        $service = factory(Service::class)->make();
        $service->options = collect([factory(ServiceOption::class)->make(['tag' => $option->tag])]);

        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('isFile')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getSize')->withNoArgs()->once()->andReturn(100);
        $this->file->shouldReceive('openFile->fread')->with(100)->once()->andReturn(json_encode([
            'meta' => ['version' => 'PTDL_v1'],
            'tag' => $option->tag,
        ]));
        $this->serviceRepository->shouldReceive('getWithOptions')->with($service->id)->once()->andReturn($service);

        try {
            $this->service->handle($this->file, $service->id);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DuplicateOptionTagException::class, $exception);
            $this->assertEquals(trans('exceptions.service.options.duplicate_tag'), $exception->getMessage());
        }
    }
}
