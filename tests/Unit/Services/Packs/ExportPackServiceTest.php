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

use Illuminate\Contracts\Filesystem\Factory;
use Mockery as m;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Models\Pack;
use Pterodactyl\Services\Packs\ExportPackService;
use Tests\TestCase;
use ZipArchive;

class ExportPackServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \ZipArchive
     */
    protected $archive;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Packs\ExportPackService
     */
    protected $service;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->archive = m::mock(ZipArchive::class);
        $this->repository = m::mock(PackRepositoryInterface::class);
        $this->storage = m::mock(Factory::class);

        $this->service = new ExportPackService($this->storage, $this->repository, $this->archive);
    }

    /**
     * Provide standard data to all tests.
     */
    protected function setupTestData()
    {
        $this->model = factory(Pack::class)->make();
        $this->json = [
            'name' => $this->model->name,
            'version' => $this->model->version,
            'description' => $this->model->description,
            'selectable' => $this->model->selectable,
            'visible' => $this->model->visible,
            'locked' => $this->model->locked,
        ];
    }

    /**
     * Test that an archive of the entire pack can be exported.
     */
    public function testFilesAreBundledIntoZipWhenRequested()
    {
        $this->setupTestData();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'tempnam')
            ->expects($this->once())->willReturn('/tmp/myfile.test');

        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fopen')->expects($this->never());

        $this->archive->shouldReceive('open')->with('/tmp/myfile.test', $this->archive::CREATE)->once()->andReturnSelf();
        $this->storage->shouldReceive('disk->files')->with('packs/' . $this->model->uuid)->once()->andReturn(['file_one']);
        $this->archive->shouldReceive('addFile')->with(storage_path('app/file_one'), 'file_one')->once()->andReturnSelf();
        $this->archive->shouldReceive('addFromString')->with('import.json', json_encode($this->json, JSON_PRETTY_PRINT))->once()->andReturnSelf();
        $this->archive->shouldReceive('close')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($this->model, true);
        $this->assertEquals('/tmp/myfile.test', $response);
    }

    /**
     * Test that the pack configuration can be saved as a json file.
     */
    public function testPackConfigurationIsSavedAsJsonFile()
    {
        $this->setupTestData();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'tempnam')
            ->expects($this->once())->willReturn('/tmp/myfile.test');
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fopen')->expects($this->once())->wilLReturn('fp');
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fwrite')
            ->expects($this->once())->with('fp', json_encode($this->json, JSON_PRETTY_PRINT))->willReturn(null);
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fclose')
            ->expects($this->once())->with('fp')->willReturn(null);

        $response = $this->service->handle($this->model);
        $this->assertEquals('/tmp/myfile.test', $response);
    }

    /**
     * Test that a model ID can be passed in place of the model itself.
     */
    public function testPackIdCanBePassedInPlaceOfModel()
    {
        $this->setupTestData();

        $this->repository->shouldReceive('find')->with($this->model->id)->once()->andReturn($this->model);
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'tempnam')->expects($this->once())->willReturn('/tmp/myfile.test');
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fopen')->expects($this->once())->wilLReturn(null);
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fwrite')->expects($this->once())->willReturn(null);
        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'fclose')->expects($this->once())->willReturn(null);

        $response = $this->service->handle($this->model->id);
        $this->assertEquals('/tmp/myfile.test', $response);
    }

    /**
     * Test that an exception is thrown when a ZipArchive cannot be created.
     *
     * @expectedException  \Pterodactyl\Exceptions\Service\Pack\ZipArchiveCreationException
     */
    public function testExceptionIsThrownIfZipArchiveCannotBeCreated()
    {
        $this->setupTestData();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Packs', 'tempnam')
            ->expects($this->once())->willReturn('/tmp/myfile.test');

        $this->archive->shouldReceive('open')->once()->andReturn(false);

        $this->service->handle($this->model, true);
    }
}
