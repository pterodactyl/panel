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

use Ramsey\Uuid\Uuid;
use Illuminate\Log\Writer;
use Illuminate\Database\DatabaseManager;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class CreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var \Pterodactyl\Services\Servers\UsernameGenerationService
     */
    protected $usernameService;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $validatorService;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * CreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface     $allocationRepository
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Illuminate\Database\DatabaseManager                                $database
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface           $nodeRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface           $userRepository
     * @param \Pterodactyl\Services\Servers\UsernameGenerationService             $usernameService
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     * @param \Illuminate\Log\Writer                                              $writer
     */
    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        DaemonServerRepositoryInterface $daemonServerRepository,
        DatabaseManager $database,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        UserRepositoryInterface $userRepository,
        UsernameGenerationService $usernameService,
        VariableValidatorService $validatorService,
        Writer $writer
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->database = $database;
        $this->nodeRepository = $nodeRepository;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->userRepository = $userRepository;
        $this->usernameService = $usernameService;
        $this->validatorService = $validatorService;
        $this->writer = $writer;
    }

    /**
     * Create a server on both the panel and daemon.
     *
     * @param  array $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $data)
    {
        // @todo auto-deployment
        $validator = $this->validatorService->isAdmin()->setFields($data['environment'])->validate($data['option_id']);
        $uniqueShort = bin2hex(random_bytes(4));

        $this->database->beginTransaction();

        $server = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'uuidShort' => $uniqueShort,
            'node_id' => $data['node_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'skip_scripts' => isset($data['skip_scripts']),
            'suspended' => false,
            'owner_id' => $data['owner_id'],
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'oom_disabled' => isset($data['oom_disabled']),
            'allocation_id' => $data['allocation_id'],
            'service_id' => $data['service_id'],
            'option_id' => $data['option_id'],
            'pack_id' => (! isset($data['pack_id']) || $data['pack_id'] == 0) ? null : $data['pack_id'],
            'startup' => $data['startup'],
            'daemonSecret' => bin2hex(random_bytes(18)),
            'image' => $data['docker_image'],
            'username' => $this->usernameService->generate($data['name'], $uniqueShort),
            'sftp_password' => null,
        ]);

        // Process allocations and assign them to the server in the database.
        $records = [$data['allocation_id']];
        if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
            $records = array_merge($records, $data['allocation_additional']);
        }

        $this->allocationRepository->assignAllocationsToServer($server->id, $records);

        // Process the passed variables and store them in the database.
        $records = [];
        foreach ($validator->getResults() as $result) {
            $records[] = [
                'server_id' => $server->id,
                'variable_id' => $result['id'],
                'variable_value' => $result['value'],
            ];
        }

        $this->serverVariableRepository->insert($records);

        // Create the server on the daemon & commit it to the database.
        try {
            $this->daemonServerRepository->setNode($server->node_id)->create($server->id);
            $this->database->commit();
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);
            $this->database->rollBack();

            throw new DisplayException(trans('admin/server.exceptions.daemon_exception', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        return $server;
    }
}
