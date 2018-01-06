<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Nodes;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Node;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class NodeUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface
     */
    protected $configRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * UpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface $configurationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface                 $repository
     * @param \Illuminate\Log\Writer                                                    $writer
     */
    public function __construct(
        ConfigurationRepositoryInterface $configurationRepository,
        NodeRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->configRepository = $configurationRepository;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Update the configuration values for a given node on the machine.
     *
     * @param int|\Pterodactyl\Models\Node $node
     * @param array                        $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($node, array $data)
    {
        if (! $node instanceof Node) {
            $node = $this->repository->find($node);
        }

        if (! is_null(array_get($data, 'reset_secret'))) {
            $data['daemonSecret'] = str_random(NodeCreationService::DAEMON_SECRET_LENGTH);
            unset($data['reset_secret']);
        }

        $updateResponse = $this->repository->withoutFreshModel()->update($node->id, $data);

        try {
            $this->configRepository->setNode($node)->update();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('exceptions.node.daemon_off_config_updated', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        return $updateResponse;
    }
}
