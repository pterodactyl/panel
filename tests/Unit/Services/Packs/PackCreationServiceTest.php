<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Packs;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Pack;
use Tests\Traits\MocksUuids;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Packs\PackCreationService;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException;

class PackCreationServiceTest extends TestCase
{
    use MocksUuids;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Illuminate\Http\UploadedFile|\Mockery\Mock
     */
    protected $file;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Packs\PackCreationService
     */
    protected $service;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory|\Mockery\Mock
     */
    protected $storage;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->file = m::mock(UploadedFile::class);
        $this->repository = m::mock(PackRepositoryInterface::class);
        $this->storage = m::mock(Factory::class);

        $this->service = new PackCreationService($this->connection, $this->storage, $this->repository);
    }

    /**
     * Test that a pack is created when no file upload is provided.
     */
    public function testPackIsCreatedWhenNoUploadedFileIsPassed()
    {
        $model = factory(Pack::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'uuid' => $this->getKnownUuid(),
            'selectable' => false,
            'visible' => false,
            'locked' => false,
            'test-data' => 'value',
        ])->once()->andReturn($model);

        $this->storage->shouldReceive('disk->makeDirectory')->with('packs/' . $model->uuid)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle(['test-data' => 'value']);
        $this->assertInstanceOf(Pack::class, $response);
        $this->assertEquals($model, $response);
    }

    /**
     * Test that a pack can be created when an uploaded file is provided.
     *
     * @dataProvider mimetypeProvider
     */
    public function testPackIsCreatedWhenUploadedFileIsProvided($mime)
    {
        $model = factory(Pack::class)->make();

        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getMimeType')->withNoArgs()->once()->andReturn($mime);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'uuid' => $this->getKnownUuid(),
            'selectable' => false,
            'visible' => false,
            'locked' => false,
            'test-data' => 'value',
        ])->once()->andReturn($model);

        $this->storage->shouldReceive('disk->makeDirectory')->with('packs/' . $model->uuid)->once()->andReturnNull();
        $this->file->shouldReceive('storeAs')->with('packs/' . $model->uuid, 'archive.tar.gz')->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle(['test-data' => 'value'], $this->file);
        $this->assertInstanceOf(Pack::class, $response);
        $this->assertEquals($model, $response);
    }

    /**
     * Test that an exception is thrown if the file upload is not valid.
     */
    public function testExceptionIsThrownIfInvalidUploadIsProvided()
    {
        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(false);

        try {
            $this->service->handle([], $this->file);
        } catch (Exception $exception) {
            $this->assertInstanceOf(InvalidFileUploadException::class, $exception);
            $this->assertEquals(trans('exceptions.packs.invalid_upload'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown when an invalid mimetype is provided.
     *
     * @dataProvider invalidMimetypeProvider
     */
    public function testExceptionIsThrownIfInvalidMimetypeIsFound($mime)
    {
        $this->file->shouldReceive('isValid')->withNoArgs()->once()->andReturn(true);
        $this->file->shouldReceive('getMimeType')->withNoArgs()->once()->andReturn($mime);

        try {
            $this->service->handle([], $this->file);
        } catch (InvalidFileMimeTypeException $exception) {
            $this->assertEquals(trans('exceptions.packs.invalid_mime', [
                'type' => implode(', ', PackCreationService::VALID_UPLOAD_TYPES),
            ]), $exception->getMessage());
        }
    }

    /**
     * Return an array of valid mimetypes to test against.
     *
     * @return array
     */
    public function mimetypeProvider()
    {
        return [
            ['application/gzip'],
            ['application/x-gzip'],
        ];
    }

    /**
     * Provide invalid mimetypes to test exceptions against.
     *
     * @return array
     */
    public function invalidMimetypeProvider()
    {
        return [
            ['application/zip'],
            ['text/plain'],
            ['image/jpeg'],
        ];
    }
}
