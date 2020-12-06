<?php

namespace Tests\Unit\Services\Nodes;

use Exception;
use Mockery as m;
use Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use Tests\Traits\MocksRequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;

class NodeUpdateServiceTest extends TestCase
{
    use PHPMock, MocksRequestException;

    /**
     * @var \Mockery\MockInterface
     */
    private $connection;

    /**
     * @var \Mockery\MockInterface
     */
    private $configurationRepository;

    /**
     * @var \Mockery\MockInterface
     */
    private $encrypter;

    /**
     * @var \Mockery\MockInterface
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->configurationRepository = m::mock(DaemonConfigurationRepository::class);
        $this->repository = m::mock(NodeRepository::class);
    }

    /**
     * Test that the daemon secret is reset when `reset_secret` is passed in the data.
     */
    public function testNodeIsUpdatedAndDaemonSecretIsReset()
    {
        /** @var \Pterodactyl\Models\Node $model */
        $model = factory(Node::class)->make([
            'fqdn' => 'https://example.com',
        ]);

        /** @var \Pterodactyl\Models\Node $updatedModel */
        $updatedModel = factory(Node::class)->make([
            'name' => 'New Name',
            'fqdn' => 'https://example2.com',
        ]);

        $this->connection->expects('transaction')->with(m::on(function ($closure) use ($updatedModel) {
            $response = $closure();

            $this->assertIsArray($response);
            $this->assertTrue(count($response) === 2);
            $this->assertSame($updatedModel, $response[0]);
            $this->assertFalse($response[1]);

            return true;
        }))->andReturns([$updatedModel, false]);

        $this->encrypter->expects('encrypt')->with(m::on(function ($value) {
            return strlen($value) === Node::DAEMON_TOKEN_LENGTH;
        }))->andReturns('encrypted_value');

        $this->repository->expects('withFreshModel->update')->with($model->id, m::on(function ($value) {
            $this->assertTrue(is_array($value));
            $this->assertSame('New Name', $value['name']);
            $this->assertSame('encrypted_value', $value['daemon_token']);
            $this->assertTrue(strlen($value['daemon_token_id']) === Node::DAEMON_TOKEN_ID_LENGTH);

            return true;
        }), true, true)->andReturns($updatedModel);

        $this->configurationRepository->expects('setNode')->with(m::on(function ($value) use ($model, $updatedModel) {
            $this->assertInstanceOf(Node::class, $value);
            $this->assertSame($model->uuid, $value->uuid);

            // Yes, this is correct. Always use the updated model's FQDN when making requests to
            // the Daemon so that any changes to that are properly propagated down to the daemon.
            //
            // @see https://github.com/pterodactyl/panel/issues/1931
            $this->assertSame($updatedModel->fqdn, $value->fqdn);

            return true;
        }))->andReturnSelf();

        $this->configurationRepository->expects('update')->with($updatedModel);

        $this->getService()->handle($model, [
            'name' => $updatedModel->name,
        ], true);
    }

    /**
     * Test that daemon secret is not modified when no variable is passed in data.
     */
    public function testNodeIsUpdatedAndDaemonSecretIsNotChanged()
    {
        /** @var \Pterodactyl\Models\Node $model */
        $model = factory(Node::class)->make(['fqdn' => 'https://example.com']);

        /** @var \Pterodactyl\Models\Node $updatedModel */
        $updatedModel = factory(Node::class)->make(['name' => 'New Name', 'fqdn' => $model->fqdn]);

        $this->connection->expects('transaction')->with(m::on(function ($closure) use ($updatedModel) {
            $response = $closure();

            $this->assertIsArray($response);
            $this->assertTrue(count($response) === 2);
            $this->assertSame($updatedModel, $response[0]);
            $this->assertFalse($response[1]);

            return true;
        }))->andReturns([$updatedModel, false]);

        $this->repository->expects('withFreshModel->update')->with($model->id, m::on(function ($value) {
            $this->assertTrue(is_array($value));
            $this->assertSame('New Name', $value['name']);
            $this->assertArrayNotHasKey('daemon_token', $value);
            $this->assertArrayNotHasKey('daemon_token_id', $value);

            return true;
        }), true, true)->andReturns($updatedModel);

        $this->configurationRepository->expects('setNode->update')->with($updatedModel);

        $this->getService()->handle($model, ['name' => $updatedModel->name]);
    }

    /**
     * Test that an exception caused by a connection error is handled.
     */
    public function testExceptionRelatedToConnection()
    {
        $this->configureExceptionMock(DaemonConnectionException::class);
        $this->expectException(ConfigurationNotPersistedException::class);

        /** @var \Pterodactyl\Models\Node $model */
        $model = factory(Node::class)->make(['fqdn' => 'https://example.com']);

        /** @var \Pterodactyl\Models\Node $updatedModel */
        $updatedModel = factory(Node::class)->make(['name' => 'New Name', 'fqdn' => $model->fqdn]);

        $this->connection->expects('transaction')->with(m::on(function ($closure) use ($updatedModel) {
            $response = $closure();

            $this->assertIsArray($response);
            $this->assertTrue(count($response) === 2);
            $this->assertSame($updatedModel, $response[0]);
            $this->assertTrue($response[1]);

            return true;
        }))->andReturn([$updatedModel, true]);

        $this->repository->expects('withFreshModel->update')->with($model->id, m::on(function ($value) {
            $this->assertTrue(is_array($value));
            $this->assertSame('New Name', $value['name']);
            $this->assertArrayNotHasKey('daemon_token', $value);
            $this->assertArrayNotHasKey('daemon_token_id', $value);

            return true;
        }), true, true)->andReturns($updatedModel);

        $this->configurationRepository->expects('setNode->update')->with($updatedModel)->andThrow(
            new DaemonConnectionException(
                new ConnectException('', new Request('GET', 'Test'), new Exception)
            )
        );

        $this->getService()->handle($model, ['name' => $updatedModel->name]);
    }

    /**
     * Test that an exception not caused by a daemon connection error is handled.
     */
    public function testExceptionNotRelatedToConnection()
    {
        /** @var \Pterodactyl\Models\Node $model */
        $model = factory(Node::class)->make(['fqdn' => 'https://example.com']);

        /** @var \Pterodactyl\Models\Node $updatedModel */
        $updatedModel = factory(Node::class)->make(['name' => 'New Name', 'fqdn' => $model->fqdn]);

        $this->connection->expects('transaction')->with(m::on(function ($closure) use ($updatedModel) {
            try {
                $closure();
            } catch (Exception $exception) {
                $this->assertInstanceOf(Exception::class, $exception);
                $this->assertSame('Foo', $exception->getMessage());

                return true;
            }

            return false;
        }));

        $this->repository->expects('withFreshModel->update')->andReturns($updatedModel);
        $this->configurationRepository->expects('setNode->update')->andThrow(
            new Exception('Foo')
        );

        $this->getService()->handle($model, ['name' => $updatedModel->name]);
    }

    /**
     * Return an instance of the service with mocked injections.
     *
     * @return \Pterodactyl\Services\Nodes\NodeUpdateService
     */
    private function getService(): NodeUpdateService
    {
        return new NodeUpdateService(
            $this->connection, $this->encrypter, $this->configurationRepository, $this->repository
        );
    }
}
