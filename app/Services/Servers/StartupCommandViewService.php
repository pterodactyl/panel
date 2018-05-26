<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class StartupCommandViewService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * StartupCommandViewService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Generate a startup command for a server and return all of the user-viewable variables
     * as well as their assigned values.
     *
     * @param int $server
     * @return \Illuminate\Support\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $server): Collection
    {
        $response = $this->repository->getVariablesWithValues($server, true);
        $server = $this->repository->getPrimaryAllocation($response->server);

        $find = ['{{SERVER_MEMORY}}', '{{SERVER_IP}}', '{{SERVER_PORT}}'];
        $replace = [$server->memory, $server->getRelation('allocation')->ip, $server->getRelation('allocation')->port];

        $variables = $server->getRelation('egg')->getRelation('variables')
            ->each(function ($variable) use (&$find, &$replace, $response) {
                $find[] = '{{' . $variable->env_variable . '}}';
                $replace[] = $variable->user_viewable ? $response->data[$variable->env_variable] : '[hidden]';
            })->filter(function ($variable) {
                return $variable->user_viewable === 1;
            });

        return collect([
            'startup' => str_replace($find, $replace, $server->startup),
            'variables' => $variables,
            'server_values' => $response->data,
        ]);
    }
}
