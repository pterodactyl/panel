<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Database;

use Exception;
use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Services\Database\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseManagementServiceTest extends TestCase
{
    use PHPMock;

    const TEST_DATA = [
        'server_id' => 1,
        'database' => 'd1_dbname',
        'remote' => '%',
        'username' => 'u1_str_random',
        'password' => 'enc_password',
        'database_host_id' => 3,
    ];

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection
     */
    protected $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Database\DatabaseManagementService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->database = m::mock(DatabaseManager::class);
        $this->dynamic = m::mock(DynamicDatabaseConnection::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(DatabaseRepositoryInterface::class);

        $this->getFunctionMock('\\Pterodactyl\\Services\\Database', 'str_random')
            ->expects($this->any())->willReturn('str_random');

        $this->service = new DatabaseManagementService(
            $this->database,
            $this->dynamic,
            $this->repository,
            $this->encrypter
        );
    }

    /**
     * Test that a new database can be created that is linked to a specific host.
     */
    public function testCreateANewDatabaseThatIsLinkedToAHost()
    {
        $this->encrypter->shouldReceive('encrypt')->with('str_random')->once()->andReturn('enc_password');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('createIfNotExists')
            ->with(self::TEST_DATA)
            ->once()
            ->andReturn((object) self::TEST_DATA);
        $this->dynamic->shouldReceive('set')
            ->with('dynamic', self::TEST_DATA['database_host_id'])
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('createDatabase')->with(
            self::TEST_DATA['database'],
            'dynamic'
        )->once()->andReturnNull();

        $this->encrypter->shouldReceive('decrypt')->with('enc_password')->once()->andReturn('str_random');
        $this->repository->shouldReceive('createUser')->with(
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'str_random',
            'dynamic'
        )->once()->andReturnNull();

        $this->repository->shouldReceive('assignUserToDatabase')->with(
            self::TEST_DATA['database'],
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'dynamic'
        )->once()->andReturnNull();

        $this->repository->shouldReceive('flush')->with('dynamic')->once()->andReturnNull();
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->create(1, [
            'database' => 'dbname',
            'remote' => '%',
            'database_host_id' => 3,
        ]);

        $this->assertNotEmpty($response);
        $this->assertTrue(is_object($response), 'Assert that response is an object.');

        $this->assertEquals(self::TEST_DATA['database'], $response->database);
        $this->assertEquals(self::TEST_DATA['remote'], $response->remote);
        $this->assertEquals(self::TEST_DATA['username'], $response->username);
        $this->assertEquals(self::TEST_DATA['password'], $response->password);
        $this->assertEquals(self::TEST_DATA['database_host_id'], $response->database_host_id);
    }

    /**
     * Test that an exception before the database is created and returned does not attempt any actions.
     *
     * @expectedException \Exception
     */
    public function testExceptionBeforeDatabaseIsCreatedShouldNotAttemptAnyRollBackOperations()
    {
        $this->encrypter->shouldReceive('encrypt')->with('str_random')->once()->andReturn('enc_password');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('createIfNotExists')
            ->with(self::TEST_DATA)
            ->once()
            ->andThrow(new Exception('Test Message'));
        $this->repository->shouldNotReceive('dropDatabase');
        $this->database->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        $this->service->create(1, [
            'database' => 'dbname',
            'remote' => '%',
            'database_host_id' => 3,
        ]);
    }

    /**
     * Test that an exception after database creation attempts to clean up previous operations.
     *
     * @expectedException \Exception
     */
    public function testExceptionAfterDatabaseCreationShouldAttemptRollBackOperations()
    {
        $this->encrypter->shouldReceive('encrypt')->with('str_random')->once()->andReturn('enc_password');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('createIfNotExists')
            ->with(self::TEST_DATA)
            ->once()
            ->andReturn((object) self::TEST_DATA);
        $this->dynamic->shouldReceive('set')
            ->with('dynamic', self::TEST_DATA['database_host_id'])
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('createDatabase')->with(
            self::TEST_DATA['database'],
            'dynamic'
        )->once()->andThrow(new Exception('Test Message'));

        $this->repository->shouldReceive('dropDatabase')
            ->with(self::TEST_DATA['database'], 'dynamic')
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('dropUser')->with(
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'dynamic'
        )->once()->andReturnNull();
        $this->repository->shouldReceive('flush')->with('dynamic')->once()->andReturnNull();

        $this->database->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        $this->service->create(1, [
            'database' => 'dbname',
            'remote' => '%',
            'database_host_id' => 3,
        ]);
    }

    /**
     * Test that an exception thrown during a rollback operation is silently handled and not returned.
     */
    public function testExceptionThrownDuringRollBackProcessShouldNotBeThrownToCallingFunction()
    {
        $this->encrypter->shouldReceive('encrypt')->with('str_random')->once()->andReturn('enc_password');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('createIfNotExists')
            ->with(self::TEST_DATA)
            ->once()
            ->andReturn((object) self::TEST_DATA);
        $this->dynamic->shouldReceive('set')
            ->with('dynamic', self::TEST_DATA['database_host_id'])
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('createDatabase')->with(
            self::TEST_DATA['database'],
            'dynamic'
        )->once()->andThrow(new Exception('Test One'));

        $this->repository->shouldReceive('dropDatabase')->with(self::TEST_DATA['database'], 'dynamic')
            ->once()->andThrow(new Exception('Test Two'));

        $this->database->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        try {
            $this->service->create(1, [
                'database' => 'dbname',
                'remote' => '%',
                'database_host_id' => 3,
            ]);
        } catch (Exception $ex) {
            $this->assertInstanceOf(Exception::class, $ex);
            $this->assertEquals('Test One', $ex->getMessage());
        }
    }

    /**
     * Test that a password can be changed for a given database.
     */
    public function testDatabasePasswordShouldBeChanged()
    {
        $this->repository->shouldReceive('find')->with(1)->once()->andReturn((object) self::TEST_DATA);
        $this->dynamic->shouldReceive('set')
            ->with('dynamic', self::TEST_DATA['database_host_id'])
            ->once()
            ->andReturnNull();
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->encrypter->shouldReceive('encrypt')->with('new_password')->once()->andReturn('new_enc_password');
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with(1, [
                'password' => 'new_enc_password',
            ])->andReturn(true);

        $this->repository->shouldReceive('dropUser')->with(
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'dynamic'
        )->once()->andReturnNull();

        $this->repository->shouldReceive('createUser')->with(
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'new_password',
            'dynamic'
        )->once()->andReturnNull();

        $this->repository->shouldReceive('assignUserToDatabase')->with(
            self::TEST_DATA['database'],
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'dynamic'
        )->once()->andReturnNull();

        $this->repository->shouldReceive('flush')->with('dynamic')->once()->andReturnNull();
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->changePassword(1, 'new_password');

        $this->assertTrue($response);
    }

    /**
     * Test that an exception thrown while changing a password will attempt a rollback.
     *
     * @expectedException \Exception
     */
    public function testExceptionThrownWhileChangingDatabasePasswordShouldRollBack()
    {
        $this->repository->shouldReceive('find')->with(1)->once()->andReturn((object) self::TEST_DATA);
        $this->dynamic->shouldReceive('set')
            ->with('dynamic', self::TEST_DATA['database_host_id'])
            ->once()
            ->andReturnNull();
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->encrypter->shouldReceive('encrypt')->with('new_password')->once()->andReturn('new_enc_password');
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with(1, [
                'password' => 'new_enc_password',
            ])->andReturn(true);

        $this->repository->shouldReceive('dropUser')->with(
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'dynamic'
        )->once()->andThrow(new Exception());

        $this->database->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        $this->service->changePassword(1, 'new_password');
    }

    /**
     * Test that a database can be deleted.
     */
    public function testDatabaseShouldBeDeleted()
    {
        $this->repository->shouldReceive('find')->with(1)->once()->andReturn((object) self::TEST_DATA);
        $this->dynamic->shouldReceive('set')
            ->with('dynamic', self::TEST_DATA['database_host_id'])
            ->once()
            ->andReturnNull();

        $this->repository->shouldReceive('dropDatabase')
            ->with(self::TEST_DATA['database'], 'dynamic')
            ->once()
            ->andReturnNull();
        $this->repository->shouldReceive('dropUser')->with(
            self::TEST_DATA['username'],
            self::TEST_DATA['remote'],
            'dynamic'
        )->once()->andReturnNull();
        $this->repository->shouldReceive('flush')->with('dynamic')->once()->andReturnNull();
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(1);

        $response = $this->service->delete(1);

        $this->assertEquals(1, $response);
    }
}
