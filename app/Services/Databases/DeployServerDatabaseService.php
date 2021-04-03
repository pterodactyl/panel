<?php

namespace Pterodactyl\Services\Databases;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Node;
use Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;

class DeployServerDatabaseService
{
    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * ServerDatabaseCreationService constructor.
     *
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService $managementService
     */
    public function __construct(DatabaseManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function handle(Server $server, array $data): Database
    {
        Assert::notEmpty($data['database'] ?? null);
        Assert::notEmpty($data['remote'] ?? null);

        $hosts = DatabaseHost::query()->get()->toBase();
        if ($hosts->isEmpty()) {
            throw new NoSuitableDatabaseHostException();
        } else {
            $nodeHosts = $hosts->where('node_id', $server->node_id)->toBase();
            if ($nodeHosts->isEmpty()) {
                /* Geting DataBase Host by Location */
                $nodes = Node::query()->get()->toBase();
                $serverNode = $nodes->where('id', $server->node_id)->toBase();
                $nodesLocation = $nodes->where('location_id', $serverNode->first()->location_id)->toArray();
                foreach ($nodesLocation as $node) {
                    $nodeHosts = $hosts->where('node_id', $node['id'])->toBase();
                    if (!$nodeHosts->isEmpty()) {
                        break;
                    }
                }
                if ($nodeHosts->isEmpty()) {
                    throw new NoSuitableDatabaseHostException();
                }
            }
        }

        return $this->managementService->create($server, [
            'database_host_id' => $nodeHosts->isEmpty()
                ? $hosts->random()->id
                : $nodeHosts->random()->id,
            'database' => DatabaseManagementService::generateUniqueDatabaseName($data['database'], $server->id),
            'remote' => $data['remote'],
        ]);
    }
}
