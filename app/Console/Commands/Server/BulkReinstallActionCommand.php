<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Server;

use Webmozart\Assert\Assert;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class BulkReinstallActionCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var string
     */
    protected $description = 'Reinstall a single server, all servers on a node, or all servers on the panel.';

    /**
     * @var string
     */
    protected $signature = 'p:server:reinstall
                            {server? : The ID of the server to reinstall.}
                            {--node= : ID of the node to reinstall all servers on. Ignored if server is passed.}';

    /**
     * BulkReinstallActionCommand constructor.
     */
    public function __construct(
        DaemonServerRepository $daemonRepository,
        ServerConfigurationStructureService $configurationStructureService,
        ServerRepository $repository
    ) {
        parent::__construct();

        $this->configurationStructureService = $configurationStructureService;
        $this->daemonRepository = $daemonRepository;
        $this->repository = $repository;
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $servers = $this->getServersToProcess();

        if (!$this->confirm(trans('command/messages.server.reinstall.confirm')) && $this->input->isInteractive()) {
            return;
        }

        $bar = $this->output->createProgressBar(count($servers));

        $servers->each(function ($server) use ($bar) {
            $bar->clear();

            try {
                $this->daemonRepository->setServer($server)->reinstall();
            } catch (RequestException $exception) {
                $this->output->error(trans('command/messages.server.reinstall.failed', [
                    'name' => $server->name,
                    'id' => $server->id,
                    'node' => $server->node->name,
                    'message' => $exception->getMessage(),
                ]));
            }

            $bar->advance();
            $bar->display();
        });

        $this->line('');
    }

    /**
     * Return the servers to be reinstalled.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getServersToProcess()
    {
        Assert::nullOrIntegerish($this->argument('server'), 'Value passed in server argument must be null or an integer, received %s.');
        Assert::nullOrIntegerish($this->option('node'), 'Value passed in node option must be null or integer, received %s.');

        return $this->repository->getDataForReinstall($this->argument('server'), $this->option('node'));
    }
}
