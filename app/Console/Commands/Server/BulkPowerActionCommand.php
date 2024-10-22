<?php

namespace Pterodactyl\Console\Commands\Server;

use Pterodactyl\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Factory as ValidatorFactory;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class BulkPowerActionCommand extends Command
{
    protected $signature = 'p:server:bulk-power
                            {action : The action to perform (start, stop, restart, kill)}
                            {--servers= : A comma separated list of servers.}
                            {--nodes= : A comma separated list of nodes.}';

    protected $description = 'Perform bulk power management on large groupings of servers or nodes at once.';

    /**
     * BulkPowerActionCommand constructor.
     */
    public function __construct(private DaemonPowerRepository $powerRepository, private ValidatorFactory $validator)
    {
        parent::__construct();
    }

    /**
     * Handle the bulk power request.
     *
     * @throws ValidationException
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

        $count = $this->getQueryBuilder($servers, $nodes)->count();
        if (!$this->confirm(trans('command/messages.server.power.confirm', ['action' => $action, 'count' => $count])) && $this->input->isInteractive()) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $powerRepository = $this->powerRepository;
        $this->getQueryBuilder($servers, $nodes)->each(function (Server $server) use ($action, $powerRepository, &$bar) {
            $bar->clear();

            try {
                $powerRepository->setServer($server)->send($action);
            } catch (DaemonConnectionException $exception) {
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

    /**
     * Returns the query builder instance that will return the servers that should be affected.
     */
    protected function getQueryBuilder(array $servers, array $nodes): Builder
    {
        $instance = Server::query()->whereNull('status');

        if (!empty($nodes) && !empty($servers)) {
            $instance->whereIn('id', $servers)->orWhereIn('node_id', $nodes);
        } elseif (empty($nodes) && !empty($servers)) {
            $instance->whereIn('id', $servers);
        } elseif (!empty($nodes) && empty($servers)) {
            $instance->whereIn('node_id', $nodes);
        }

        return $instance->with('node');
    }
}
