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

namespace Tests\Unit\Services\Services;

use Mockery as m;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Models\Service;
use Pterodactyl\Services\Services\ServiceCreationService;
use Pterodactyl\Traits\Services\CreatesServiceIndex;
use Tests\TestCase;
use Illuminate\Contracts\Config\Repository;

class ServiceCreationServiceTest extends TestCase
{
    use CreatesServiceIndex;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\ServiceCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(ServiceRepositoryInterface::class);

        $this->service = new ServiceCreationService($this->config, $this->repository);
    }

    /**
     * Test that a new service can be created using the correct data.
     */
    public function testCreateNewService()
    {
        $model = factory(Service::class)->make();
        $data = [
            'name' => $model->name,
            'description' => $model->description,
            'folder' => $model->folder,
            'startup' => $model->startup,
        ];

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('0000-author');
        $this->repository->shouldReceive('create')->with([
            'author' => '0000-author',
            'name' => $data['name'],
            'description' => $data['description'],
            'folder' => $data['folder'],
            'startup' => $data['startup'],
            'index_file' => $this->getIndexScript(),
        ])->once()->andReturn($model);

        $response = $this->service->handle($data);
        $this->assertInstanceOf(Service::class, $response);
        $this->assertEquals($model, $response);
    }
}
