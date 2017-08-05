<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services\Nodes;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Log\Writer;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Node;

class UpdateService
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
     * @param  int|\Pterodactyl\Models\Node $node
     * @param  array                        $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle($node, array $data)
    {
        if (! $node instanceof Node) {
            $node = $this->repository->find($node);
        }

        if (! is_null(array_get($data, 'reset_secret'))) {
            $data['daemonSecret'] = bin2hex(random_bytes(CreationService::DAEMON_SECRET_LENGTH));
            unset($data['reset_secret']);
        }

        $updateResponse = $this->repository->withoutFresh()->update($node->id, $data);

        try {
            $this->configRepository->setNode($node->id)->setAccessToken($node->daemonSecret)->update();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/exceptions.node.daemon_off_config_updated', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        return $updateResponse;
    }
}
