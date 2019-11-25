<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Traits\Services\ReturnsUpdatedModels;
use Pterodactyl\Repositories\Eloquent\ServerRepository;

class DetailsModificationService
{
    use ReturnsUpdatedModels;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * DetailsModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        ServerRepository $repository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
    }

    /**
     * Update the details for a single server instance.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array $data
     * @return bool|\Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, array $data)
    {
        $this->connection->beginTransaction();

        $response = $this->repository->setFreshModel($this->getUpdatedModel())->update($server->id, [
            'external_id' => array_get($data, 'external_id'),
            'owner_id' => array_get($data, 'owner_id'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description') ?? '',
        ], true, true);

        $this->connection->commit();

        return $response;
    }
}
