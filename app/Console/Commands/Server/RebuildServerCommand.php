<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Console\Commands\Server;

use Webmozart\Assert\Assert;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\RequestException;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Services\Servers\ServerConfigurationStructureService;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class RebuildServerCommand extends Command
{
    /**
     * @var \App\Services\Servers\ServerConfigurationStructureService
     */
    protected $configurationStructureService;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var string
     */
    protected $description = 'Rebuild a single server, all servers on a node, or all servers on the panel.';

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $signature = 'p:server:rebuild
                            {server? : The ID of the server to rebuild.}
                            {--node= : ID of the node to rebuild all servers on. Ignored if server is passed.}';

    /**
     * RebuildServerCommand constructor.
     *
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \App\Services\Servers\ServerConfigurationStructureService  $configurationStructureService
     * @param \App\Contracts\Repository\ServerRepositoryInterface        $repository
     */
    public function __construct(
        DaemonServerRepositoryInterface $daemonRepository,
        ServerConfigurationStructureService $configurationStructureService,
        ServerRepositoryInterface $repository
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
        $bar = $this->output->createProgressBar(count($servers));

        $servers->each(function ($server) use ($bar) {
            $bar->clear();
            $json = array_merge($this->configurationStructureService->handle($server), ['rebuild' => true]);

            try {
                $this->daemonRepository->setServer($server)->update($json);
            } catch (RequestException $exception) {
                $this->output->error(trans('command/messages.server.rebuild_failed', [
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
     * Return the servers to be rebuilt.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getServersToProcess()
    {
        Assert::nullOrIntegerish($this->argument('server'), 'Value passed in server argument must be null or an integer, received %s.');
        Assert::nullOrIntegerish($this->option('node'), 'Value passed in node option must be null or integer, received %s.');

        return $this->repository->getDataForRebuild($this->argument('server'), $this->option('node'));
    }
}
