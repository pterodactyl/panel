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
     * @param bool                     $resetToken
     *
     * @return \Pterodactyl\Models\Node
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException
     */
    public function handle(Node $node, array $data, bool $resetToken = false)
    {
        if ($resetToken) {
            $data['daemonSecret'] = str_random(Node::DAEMON_SECRET_LENGTH);
        }

        $this->connection->beginTransaction();

        /** @var \Pterodactyl\Models\Node $updatedModel */
        $updatedModel = $this->repository->update($node->id, $data);

        try {
            if ($resetToken) {
                // We need to clone the new model and set it's authentication token to be the
                // old one so we can connect. Then we will pass the new token through as an
                // override on the call.
                $cloned = $updatedModel->replicate(['daemonSecret']);
                $cloned->setAttribute('daemonSecret', $node->getAttribute('daemonSecret'));

                $this->configRepository->setNode($cloned)->update([
                    'keys' => [$data['daemonSecret']],
                ]);
            } else {
                $this->configRepository->setNode($updatedModel)->update();
            }

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
