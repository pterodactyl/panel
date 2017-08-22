<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Services\Packs;

use Exception;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use Mockery as m;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException;
use Pterodactyl\Models\Pack;
use Pterodactyl\Services\Packs\PackCreationService;
use Tests\TestCase;

class PackCreationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Illuminate\Http\UploadedFile
     */
    protected $file;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Packs\PackCreationService
     */
    protected $service;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * @var \Ramsey\Uuid\Uuid
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
        $this->repository = m::mock(PackRepositoryInterface::class);
        $this->storage = m::mock(Factory::class);
        $this->uuid = m::mock('overload:\Ramsey\Uuid\Uuid');

        $this->service = new PackCreationService($this->connection, $this->storage, $this->repository);
    }

    /**
     * Test that a pack is created when no file upload is provided.
     */
    public function testPackIsCreatedWhenNoUploadedFileIsPassed()
    {
        $model = factory(Pack::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->uuid->shouldReceive('uuid4')->withNoArgs()->once()->andReturn($model->uuid);
        $this->repository->shouldReceive('create')->with([
            'uuid' => $model->uuid,
            'selectable' => false,
            'visible' => false,
            'locked' => false,
            'test-data' => 'value',
        ])->once()->andReturn($model);

        $this->storage->shouldReceive('disk')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('makeDirectory')->with('packs/' . $model->uuid)->once()->andReturnNull();
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
        $this->uuid->shouldReceive('uuid4')->withNoArgs()->once()->andReturn($model->uuid);
        $this->repository->shouldReceive('create')->with([
            'uuid' => $model->uuid,
            'selectable' => false,
            'visible' => false,
            'locked' => false,
            'test-data' => 'value',
        ])->once()->andReturn($model);

        $this->storage->shouldReceive('disk')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('makeDirectory')->with('packs/' . $model->uuid)->once()->andReturnNull();
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
            $this->assertEquals(trans('admin/exceptions.packs.invalid_upload'), $exception->getMessage());
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
            $this->assertEquals(trans('admin/exceptions.packs.invalid_mime', [
                'type' => implode(', ', PackCreationService::VALID_UPLOAD_TYPES),
            ]), $exception->getMessage());
        }
    }

    /**
     * Return an array of valid mimetypes to test aganist.
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
     * Provide invalid mimetypes to test exceptions aganist.
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
