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

namespace Tests\Unit\Services\Administrative;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseHostInterface;
use Pterodactyl\Services\Database\DatabaseHostService;

class DatabaseHostServiceTest extends TestCase
{
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
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Database\DatabaseHostService
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
        $this->repository = m::mock(DatabaseHostInterface::class);

        $this->service = new DatabaseHostService(
            $this->repository,
            $this->database,
            $this->dynamic,
            $this->encrypter
        );
    }

    /**
     * Test that creating a host returns the correct data.
     */
    public function test_create_host_function()
    {
        $data = [
            'password' => 'raw-password',
            'name' => 'HostName',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'someusername',
            'node_id' => null,
        ];

        $finalData = (object) array_replace($data, ['password' => 'enc-password']);

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('raw-password')->once()->andReturn('enc-password');

        $this->repository->shouldReceive('create')->with([
            'password' => 'enc-password',
            'name' => 'HostName',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'someusername',
            'max_databases' => null,
            'node_id' => null,
        ])->once()->andReturn($finalData);

        $this->dynamic->shouldReceive('set')->with('dynamic', $finalData)->once()->andReturnNull();
        $this->database->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf()
            ->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->create($data);

        $this->assertNotNull($response);
        $this->assertTrue(is_object($response), 'Assert that response is an object.');

        $this->assertEquals('enc-password', $response->password);
        $this->assertEquals('HostName', $response->name);
        $this->assertEquals('127.0.0.1', $response->host);
        $this->assertEquals(3306, $response->port);
        $this->assertEquals('someusername', $response->username);
        $this->assertNull($response->node_id);
    }

    /**
     * Test that passing a password will store an encrypted version in the DB.
     */
    public function test_update_with_password()
    {
        $finalData = (object) ['password' => 'enc-pass', 'host' => '123.456.78.9'];

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('raw-pass')->once()->andReturn('enc-pass');

        $this->repository->shouldReceive('update')->with(1, [
            'password' => 'enc-pass',
            'host' => '123.456.78.9',
        ])->once()->andReturn($finalData);

        $this->dynamic->shouldReceive('set')->with('dynamic', $finalData)->once()->andReturnNull();
        $this->database->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf()
            ->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->update(1, ['password' => 'raw-pass', 'host' => '123.456.78.9']);

        $this->assertNotNull($response);
        $this->assertEquals('enc-pass', $response->password);
        $this->assertEquals('123.456.78.9', $response->host);
    }

    /**
     * Test that passing no or empty password will skip storing it
     */
    public function test_update_without_password()
    {
        $finalData = (object) ['host' => '123.456.78.9'];

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldNotReceive('encrypt');

        $this->repository->shouldReceive('update')->with(1, ['host' => '123.456.78.9'])->once()->andReturn($finalData);

        $this->dynamic->shouldReceive('set')->with('dynamic', $finalData)->once()->andReturnNull();
        $this->database->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf()
            ->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->update(1, ['password' => '', 'host' => '123.456.78.9']);

        $this->assertNotNull($response);
        $this->assertEquals('123.456.78.9', $response->host);
    }

    /**
     * Test that a database host can be deleted.
     */
    public function test_delete_function()
    {
        $this->repository->shouldReceive('deleteIfNoDatabases')->with(1)->once()->andReturn(true);

        $response = $this->service->delete(1);

        $this->assertTrue($response, 'Assert that response is true.');
    }
}
