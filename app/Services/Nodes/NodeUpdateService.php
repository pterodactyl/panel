<?php

namespace Pterodactyl\Services\Nodes;

use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Repositories\Daemon\ConfigurationRepository;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;

class NodeUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * UpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param \Pterodactyl\Repositories\Wings\DaemonConfigurationRepository $configurationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        Encrypter $encrypter,
        DaemonConfigurationRepository $configurationRepository,
        NodeRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->configurationRepository = $configurationRepository;
        $this->encrypter = $encrypter;
    }

    /**
     * Update the configuration values for a given node on the machine.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param array $data
     * @param bool $resetToken
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
            $data['daemon_token'] = Str::random(Node::DAEMON_TOKEN_LENGTH);
            $data['daemon_token_id'] = $this->encrypter->encrypt(
                Str::random(Node::DAEMON_TOKEN_ID_LENGTH)
            );
        }

        $this->connection->beginTransaction();

        /** @var \Pterodactyl\Models\Node $updatedModel */
        $updatedModel = $this->repository->update($node->id, $data);

        try {
            if ($resetToken) {
                // We need to clone the new model and set it's authentication token to be the
                // old one so we can connect. Then we will pass the new token through as an
                // override on the call.
                $cloned = $updatedModel->replicate(['daemon_token']);
                $cloned->setAttribute('daemon_token', $node->getAttribute('daemon_token'));

                $this->configurationRepository->setNode($cloned)->update([
                    'daemon_token_id' => $updatedModel->daemon_token_id,
                    'daemon_token' => $updatedModel->getDecryptedKey(),
                ]);
            } else {
                $this->configurationRepository->setNode($updatedModel)->update();
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
