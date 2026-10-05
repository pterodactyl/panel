<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Environment;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use PDOException;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

#[Description('Configure database settings for the Panel.')]
#[Signature('p:environment:database
                            {--host= : The connection address for the MySQL server.}
                            {--port= : The connection port for the MySQL server.}
                            {--database= : The database to use.}
                            {--username= : Username to use when connecting.}
                            {--password= : Password to use for this database.}')]
class DatabaseSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    /** @var array<string, EnvironmentValue> */
    protected array $variables = [];

    /**
     * DatabaseSettingsCommand constructor.
     */
    public function __construct(private DatabaseManager $database, private Kernel $console)
    {
        parent::__construct();
    }

    /**
     * Handle command execution.
     *
     * @throws PterodactylException
     */
    public function handle(): int
    {
        $this->output->note('It is highly recommended to not use "localhost" as your database host as we have seen frequent socket connection issues. If you want to use a local connection you should be using "127.0.0.1".');
        $this->variables['DB_HOST'] = JsonValueGuard::string($this->option('host') ?? $this->ask(
            'Database Host',
            JsonValueGuard::nullableScalarString(config('database.connections.mysql.host', '127.0.0.1'))
        ));

        $this->variables['DB_PORT'] = JsonValueGuard::scalar($this->option('port') ?? $this->ask(
            'Database Port',
            JsonValueGuard::nullableScalarString(config('database.connections.mysql.port', 3306))
        ));

        $this->variables['DB_DATABASE'] = JsonValueGuard::string($this->option('database') ?? $this->ask(
            'Database Name',
            JsonValueGuard::nullableScalarString(config('database.connections.mysql.database', 'panel'))
        ));

        $this->output->note('Using the "root" account for MySQL connections is not only highly frowned upon, it is also not allowed by this application. You\'ll need to have created a MySQL user for this software.');
        $this->variables['DB_USERNAME'] = JsonValueGuard::string($this->option('username') ?? $this->ask(
            'Database Username',
            JsonValueGuard::nullableScalarString(config('database.connections.mysql.username', 'pterodactyl'))
        ));

        $askForMySQLPassword = true;
        if (! empty(config('database.connections.mysql.password')) && $this->input->isInteractive()) {
            $this->variables['DB_PASSWORD'] = JsonValueGuard::scalar(config('database.connections.mysql.password'));
            $askForMySQLPassword = $this->confirm('It appears you already have a MySQL connection password defined, would you like to change it?');
        }

        if ($askForMySQLPassword) {
            $this->variables['DB_PASSWORD'] = JsonValueGuard::scalar($this->option('password') ?? $this->secret('Database Password'));
        }

        try {
            $this->testMySQLConnection();
        } catch (PDOException $pdoException) {
            $this->output->error(sprintf('Unable to connect to the MySQL server using the provided credentials. The error returned was "%s".', $pdoException->getMessage()));
            $this->output->error('Your connection credentials have NOT been saved. You will need to provide valid connection information before proceeding.');

            if ($this->confirm('Go back and try again?')) {
                $this->database->disconnect('_pterodactyl_command_test');

                return $this->handle();
            }

            return 1;
        }

        $this->writeToEnvironment($this->variables);

        $this->info($this->console->output());

        return 0;
    }

    /**
     * Test that we can connect to the provided MySQL instance and perform a selection.
     */
    private function testMySQLConnection(): void
    {
        config()->set('database.connections._pterodactyl_command_test', [
            'driver' => 'mysql',
            'host' => $this->variables['DB_HOST'],
            'port' => $this->variables['DB_PORT'],
            'database' => $this->variables['DB_DATABASE'],
            'username' => $this->variables['DB_USERNAME'],
            'password' => $this->variables['DB_PASSWORD'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => true,
        ]);

        $this->database->connection('_pterodactyl_command_test')->getPdo();
    }
}
