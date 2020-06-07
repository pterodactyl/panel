<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Provides an exported sql file of the panels current database.';

    /**
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();
        $this->config = $config;
        $this->process = new Process(sprintf(
            'mysqldump -h %s -P %s -u%s -p%s %s > %s',
            $this->config->get('database.connections.mysql.host'),
            $this->config->get('database.connections.mysql.port'),
            $this->config->get('database.connections.mysql.username'),
            $this->config->get('database.connections.mysql.password'),
            $this->config->get('database.connections.mysql.database'),
            storage_path('../' . $this->config->get('database.connections.mysql.database') . '-' . date('Y-m-d-H-i') . '.sql')
        ));
    }

    /**
     * Run the command.
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->process->mustRun();
            $this->info('The database backup has completed successfully.');
        } catch (ProcessFailedException $exception) {
            $this->error('The backup process has failed...');
            $this->error($exception->getMessage());
        }
    }
}
