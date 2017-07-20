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
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class ServerService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    protected $database;
    protected $repository;
    protected $usernameService;
    protected $serverVariableRepository;
    protected $daemonServerRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $validatorService;

    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        ConnectionInterface $database,
        ServerRepositoryInterface $repository,
        DaemonServerRepositoryInterface $daemonServerRepository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        NodeRepositoryInterface $nodeRepository,
        UsernameGenerationService $usernameService,
        UserRepositoryInterface $userRepository,
        VariableValidatorService $validatorService
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->database = $database;
        $this->repository = $repository;
        $this->nodeRepository = $nodeRepository;
        $this->userRepository = $userRepository;
        $this->usernameService = $usernameService;
        $this->validatorService = $validatorService;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    public function create(array $data)
    {
        // @todo auto-deployment and packs
        $data['user_id'] = 1;

        $node = $this->nodeRepository->find($data['node_id']);
        $validator = $this->validatorService->setAdmin()->setFields($data['environment'])->validate($data['option_id']);
        $uniqueShort = bin2hex(random_bytes(4));

        $this->database->beginTransaction();

        $server = $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'uuidShort' => bin2hex(random_bytes(4)),
            'node_id' => $data['node_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'skip_scripts' => isset($data['skip_scripts']),
            'suspended' => false,
            'owner_id' => $data['user_id'],
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'oom_disabled' => isset($data['oom_disabled']),
            'allocation_id' => $data['allocation_id'],
            'service_id' => $data['service_id'],
            'option_id' => $data['option_id'],
            'pack_id' => ($data['pack_id'] == 0) ? null : $data['pack_id'],
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
        $this->daemonServerRepository->setNode($server->node_id)->setAccessToken($node->daemonSecret)->create($server->id);
        $this->database->rollBack();

        return $server;
    }
}
