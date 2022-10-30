<?php

namespace Pterodactyl\Tests\Traits;

use PDO;
use Mockery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;

trait MocksPdoConnection
{
    private static ?ConnectionResolverInterface $initialResolver;

    /**
     * Generates a mock PDO connection and injects it into the models so that any actual
     * DB call can be properly intercepted.
     */
    protected function mockPdoConnection(): Mockery\MockInterface
    {
        self::$initialResolver = Model::getConnectionResolver();

        Model::unsetConnectionResolver();

        $connection = new MySqlConnection($mock = Mockery::mock(PDO::class), 'testing_mock');
        $resolver = new ConnectionResolver(['mocked' => $connection]);
        $resolver->setDefaultConnection('mocked');

        Model::setConnectionResolver($resolver);

        return $mock;
    }

    /**
     * Resets the mock state.
     */
    protected function tearDownPdoMock(): void
    {
        if (!self::$initialResolver) {
            return;
        }

        Model::setConnectionResolver(self::$initialResolver);

        self::$initialResolver = null;
    }
}
