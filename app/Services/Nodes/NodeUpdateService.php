<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Traits\Services\ReturnsUpdatedModels;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class NodeUpdateService
{
    use ReturnsUpdatedModels;

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
     * @return \Pterodactyl\Models\Node|mixed
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
        if ($this->getUpdatedModel()) {
            $response = $this->repository->update($node->id, $data);
        } else {
            $response = $this->repository->withoutFreshModel()->update($node->id, $data);
        }

        try {
            $this->configRepository->setNode($node)->update();
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

        return $response;
    }
}
