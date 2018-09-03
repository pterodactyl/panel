<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class NodeUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface
     */
    private $configRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * UpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                                  $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface $configurationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface                 $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        ConfigurationRepositoryInterface $configurationRepository,
        NodeRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->configRepository = $configurationRepository;
        $this->repository = $repository;
    }

    /**
     * Update the configuration values for a given node on the machine.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param array                    $data
     * @return \Pterodactyl\Models\Node
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Node $node, array $data)
    {
        if (! is_null(array_get($data, 'reset_secret'))) {
            $data['daemonSecret'] = str_random(Node::DAEMON_SECRET_LENGTH);
            unset($data['reset_secret']);
        }

        $this->connection->beginTransaction();
        $updatedModel = $this->repository->update($node->id, $data);

        try {
            $this->configRepository->setNode($updatedModel)->update();
            $this->connection->commit();
        } catch (RequestException $exception) {
            // Failed to connect to the Daemon. Let's go ahead and save the configuration
            // and let the user know they'll need to manually update.
            if ($exception instanceof ConnectException) {
                $this->connection->commit();

                throw new ConfigurationNotPersistedException(trans('exceptions.node.daemon_off_config_updated'));
            }

            throw new DaemonConnectionException($exception);
        }

        return $updatedModel;
    }
}
