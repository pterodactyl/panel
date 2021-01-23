<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Environment;

use PDOException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class DatabaseSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    protected $console;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var string
     */
    protected $description = 'Configure database settings for the Panel.';

    /**
     * @var string
     */
    protected $signature = 'p:environment:database
                            {--host= : The connection address for the MySQL server.}
                            {--port= : The connection port for the MySQL server.}
                            {--database= : The database to use.}
                            {--username= : Username to use when connecting.}
                            {--password= : Password to use for this database.}';

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * DatabaseSettingsCommand constructor.
     */
    public function __construct(ConfigRepository $config, DatabaseManager $database, Kernel $console)
    {
        parent::__construct();

        $this->config = $config;
        $this->console = $console;
        $this->database = $database;
    }

    /**
     * Handle command execution.
     *
     * @return int
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function handle()
    {
        $this->output->note(trans('command/messages.environment.database.host_warning'));
        $this->variables['DB_HOST'] = $this->option('host') ?? $this->ask(
            trans('command/messages.environment.database.host'),
            $this->config->get('database.connections.mysql.host', '127.0.0.1')
        );

        $this->variables['DB_PORT'] = $this->option('port') ?? $this->ask(
            trans('command/messages.environment.database.port'),
            $this->config->get('database.connections.mysql.port', 3306)
        );

        $this->variables['DB_DATABASE'] = $this->option('database') ?? $this->ask(
            trans('command/messages.environment.database.database'),
            $this->config->get('database.connections.mysql.database', 'panel')
        );

        $this->output->note(trans('command/messages.environment.database.username_warning'));
        $this->variables['DB_USERNAME'] = $this->option('username') ?? $this->ask(
            trans('command/messages.environment.database.username'),
            $this->config->get('database.connections.mysql.username', 'pterodactyl')
        );

        $askForMySQLPassword = true;
        if (!empty($this->config->get('database.connections.mysql.password')) && $this->input->isInteractive()) {
            $this->variables['DB_PASSWORD'] = $this->config->get('database.connections.mysql.password');
            $askForMySQLPassword = $this->confirm(trans('command/messages.environment.database.password_defined'));
        }

        if ($askForMySQLPassword) {
            $this->variables['DB_PASSWORD'] = $this->option('password') ?? $this->secret(trans('command/messages.environment.database.password'));
        }

        try {
            $this->testMySQLConnection();
        } catch (PDOException $exception) {
            $this->output->error(trans('command/messages.environment.database.connection_error', ['error' => $exception->getMessage()]));
            $this->output->error(trans('command/messages.environment.database.creds_not_saved'));

            if ($this->confirm(trans('command/messages.environment.database.try_again'))) {
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
    private function testMySQLConnection()
    {
        $this->config->set('database.connections._pterodactyl_command_test', [
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
