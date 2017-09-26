<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Service;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Traits\Services\CreatesServiceIndex;
use Pterodactyl\Services\Services\ServiceCreationService;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

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
