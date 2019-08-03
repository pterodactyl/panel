<?php

namespace Pterodactyl\Console\Commands\Server;

use Illuminate\Console\Command;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Factory as ValidatorFactory;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;

class BulkPowerActionCommand extends Command
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface
     */
    private $powerRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Illuminate\Validation\Factory
     */
    private $validator;

    /**
     * @var string
     */
    protected $signature = 'p:server:bulk-power
                            {action : The action to perform (start, stop, restart, kill)}
                            {--servers= : A comma separated list of servers.}
                            {--nodes= : A comma separated list of nodes.}';

    /**
     * @var string
     */
    protected $description = 'Perform bulk power management on large groupings of servers or nodes at once.';

    /**
     * BulkPowerActionCommand constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface $powerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface       $repository
     * @param \Illuminate\Validation\Factory                                    $validator
     */
    public function __construct(
        PowerRepositoryInterface $powerRepository,
        ServerRepositoryInterface $repository,
        ValidatorFactory $validator
    ) {
        parent::__construct();

        $this->powerRepository = $powerRepository;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * Handle the bulk power request.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException
     */
    public function handle()
    {
        $action = $this->argument('action');
        $nodes = empty($this->option('nodes')) ? [] : explode(',', $this->option('nodes'));
        $servers = empty($this->option('servers')) ? [] : explode(',', $this->option('servers'));

        $validator = $this->validator->make([
            'action' => $action,
            'nodes' => $nodes,
            'servers' => $servers,
        ], [
            'action' => 'string|in:start,stop,kill,restart',
            'nodes' => 'array',
            'nodes.*' => 'integer|min:1',
            'servers' => 'array',
            'servers.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            foreach ($validator->getMessageBag()->all() as $message) {
                $this->output->error($message);
            }

            throw new ValidationException($validator);
        }

        $count = $this->repository->getServersForPowerActionCount($servers, $nodes);
        if (! $this->confirm(trans('command/messages.server.power.confirm', ['action' => $action, 'count' => $count]))) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $servers = $this->repository->getServersForPowerAction($servers, $nodes);

        foreach ($servers as $server) {
            $bar->clear();

            try {
                $this->powerRepository
                    ->setNode($server->node)
                    ->setServer($server)
                    ->sendSignal($action);
            } catch (RequestException $exception) {
                $this->output->error(trans('command/messages.server.power.action_failed', [
                    'name' => $server->name,
                    'id' => $server->id,
                    'node' => $server->node->name,
                    'message' => $exception->getMessage(),
                ]));
            }

            $bar->advance();
            $bar->display();
        }

        $this->line('');
    }
}
