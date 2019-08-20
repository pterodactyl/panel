<?php

namespace App\Services\Nodes;

use App\Models\Node;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Exceptions\Service\Node\ConfigurationNotPersistedException;
use App\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class NodeUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\Daemon\ConfigurationRepositoryInterface
     */
    private $configRepository;

    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * UpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                                  $connection
     * @param \App\Contracts\Repository\Daemon\ConfigurationRepositoryInterface $configurationRepository
     * @param \App\Contracts\Repository\NodeRepositoryInterface                 $repository
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
     * @param \App\Models\Node $node
     * @param array                    $data
     * @param bool                     $resetToken
     *
     * @return \App\Models\Node
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Service\Node\ConfigurationNotPersistedException
     */
    public function handle(Node $node, array $data, bool $resetToken = false)
    {
        if ($resetToken) {
            $data['daemonSecret'] = str_random(Node::DAEMON_SECRET_LENGTH);
        }

        $this->connection->beginTransaction();

        /** @var \App\Models\Node $updatedModel */
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
