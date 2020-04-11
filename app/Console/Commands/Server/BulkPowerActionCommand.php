<?php

namespace Pterodactyl\Console\Commands\Server;

use Illuminate\Console\Command;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Factory as ValidatorFactory;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class BulkPowerActionCommand extends Command
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonPowerRepository
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
     * @param \Pterodactyl\Repositories\Wings\DaemonPowerRepository $powerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Illuminate\Validation\Factory $validator
     */
    public function __construct(
        DaemonPowerRepository $powerRepository,
        ServerRepositoryInterface $repository,
        ValidatorFactory $validator
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->validator = $validator;
        $this->powerRepository = $powerRepository;
    }

    /**
     * Handle the bulk power request.
     *
     * @throws \Illuminate\Validation\ValidationException
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
        if (! $this->confirm(trans('command/messages.server.power.confirm', ['action' => $action, 'count' => $count])) && $this->input->isInteractive()) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $servers = $this->repository->getServersForPowerAction($servers, $nodes);

        $servers->each(function ($server) use ($action, &$bar) {
            $bar->clear();

            try {
                $this->powerRepository
                    ->setNode($server->node)
                    ->setServer($server)
                    ->send($action);
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
        });

        $this->line('');
    }
}
