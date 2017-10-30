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
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Nest;
use Tests\Traits\MocksUuids;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Services\Nests\NestCreationService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;

class NestCreationServiceTest extends TestCase
{
    use MocksUuids;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nests\NestCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(NestRepositoryInterface::class);

        $this->service = new NestCreationService($this->config, $this->repository);
    }

    /**
     * Test that a new service can be created using the correct data.
     */
    public function testCreateNewService()
    {
        $model = factory(Nest::class)->make();
        $data = [
            'name' => $model->name,
            'description' => $model->description,
        ];

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('testauthor@example.com');
        $this->repository->shouldReceive('create')->with([
            'uuid' => $this->getKnownUuid(),
            'author' => 'testauthor@example.com',
            'name' => $data['name'],
            'description' => $data['description'],
        ], true, true)->once()->andReturn($model);

        $response = $this->service->handle($data);
        $this->assertInstanceOf(Nest::class, $response);
        $this->assertEquals($model, $response);
    }
}
