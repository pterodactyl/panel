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

namespace Pterodactyl\Services\Servers;

use Illuminate\Log\Writer;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class StartupModificationService
{
    /**
     * @var bool
     */
    protected $admin = false;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    protected $environmentService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $validatorService;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * StartupModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                            $database
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Pterodactyl\Services\Servers\EnvironmentService                    $environmentService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     * @param \Illuminate\Log\Writer                                              $writer
     */
    public function __construct(
        ConnectionInterface $database,
        DaemonServerRepositoryInterface $daemonServerRepository,
        EnvironmentService $environmentService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService,
        Writer $writer
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
        $this->environmentService = $environmentService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
        $this->writer = $writer;
    }

    /**
     * Determine if this function should run at an administrative level.
     *
     * @param  bool $bool
     * @return $this
     */
    public function isAdmin($bool = true)
    {
        $this->admin = $bool;

        return $this;
    }

    /**
     * Process startup modification for a server.
     *
     * @param  int|\Pterodactyl\Models\Server $server
     * @param  array                          $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle($server, array $data)
    {
        if (! $server instanceof Server) {
            $server = $this->repository->find($server);
        }

        if (
            $server->service_id != array_get($data, 'service_id', $server->service_id) ||
            $server->option_id != array_get($data, 'option_id', $server->option_id) ||
            $server->pack_id != array_get($data, 'pack_id', $server->pack_id)
        ) {
            $hasServiceChanges = true;
        }

        $this->database->beginTransaction();
        if (isset($data['environment'])) {
            $validator = $this->validatorService->isAdmin($this->admin)
                ->setFields($data['environment'])
                ->validate(array_get($data, 'option_id', $server->option_id));

            foreach ($validator->getResults() as $result) {
                $this->serverVariableRepository->withoutFresh()->updateOrCreate([
                    'server_id' => $server->id,
                    'variable_id' => $result['id'],
                ], [
                    'variable_value' => $result['value'],
                ]);
            }
        }

        $daemonData = [
            'build' => [
                'env|overwrite' => $this->environmentService->process($server),
            ],
        ];

        if ($this->admin) {
            $server = $this->repository->update($server->id, [
                'installed' => 0,
                'startup' => array_get($data, 'startup', $server->startup),
                'service_id' => array_get($data, 'service_id', $server->service_id),
                'option_id' => array_get($data, 'option_id', $server->service_id),
                'pack_id' => array_get($data, 'pack_id', $server->pack_id),
                'skip_scripts' => isset($data['skip_scripts']),
            ]);

            if (isset($hasServiceChanges)) {
                $daemonData['service'] = array_merge(
                    $this->repository->withColumns(['id', 'option_id', 'pack_id'])->getDaemonServiceData($server),
                    ['skip_scripts' => isset($data['skip_scripts'])]
                );
            }
        }

        try {
            $this->daemonServerRepository->setNode($server->node_id)->setAccessServer($server->uuid)->update($daemonData);
            $this->database->commit();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }
    }
}
