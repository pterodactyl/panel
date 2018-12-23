<?php

namespace Tests\Unit\Services\Databases;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabasePasswordServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    private $connection;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection|\Mockery\Mock
     */
    private $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter|\Mockery\Mock
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->dynamic = m::mock(DynamicDatabaseConnection::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(DatabaseRepositoryInterface::class);
    }

    /**
     * Test that a password can be updated.
     *
     * @dataProvider useModelDataProvider
     */
    public function testPasswordIsChanged(bool $useModel)
    {
        $model = factory(Database::class)->make();

        if (! $useModel) {
            $this->repository->shouldReceive('find')->with(1234)->once()->andReturn($model);
        }

        $this->dynamic->shouldReceive('set')->with('dynamic', $model->database_host_id)->once()->andReturnNull();
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('test123')->once()->andReturn('enc123');

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf();
        $this->repository->shouldReceive('update')->with($model->id, ['password' => 'enc123'])->once()->andReturn(true);

        $this->repository->shouldReceive('dropUser')->with($model->username, $model->remote)->once()->andReturn(true);
        $this->repository->shouldReceive('createUser')->with($model->username, $model->remote, 'test123')->once()->andReturn(true);
        $this->repository->shouldReceive('assignUserToDatabase')->with($model->database, $model->username, $model->remote)->once()->andReturn(true);
        $this->repository->shouldReceive('flush')->withNoArgs()->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $response = $this->getService()->handle($useModel ? $model : 1234, 'test123');
        $this->assertNotEmpty($response);
        $this->assertTrue($response);
    }

    /**
     * Data provider to determine if a model should be passed or an int.
     *
     * @return array
     */
    public function useModelDataProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Databases\DatabasePasswordService
     */
    private function getService(): DatabasePasswordService
    {
        return new DatabasePasswordService($this->connection, $this->repository, $this->dynamic, $this->encrypter);
    }
}
