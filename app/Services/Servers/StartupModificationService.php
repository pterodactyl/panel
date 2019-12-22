<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Traits\Services\HasUserLevels;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class StartupModificationService
{
    use HasUserLevels;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    private $eggRepository;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    private $environmentService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    private $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    private $validatorService;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private $structureService;

    /**
     * StartupModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $eggRepository
     * @param \Pterodactyl\Services\Servers\EnvironmentService $environmentService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService $structureService
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Services\Servers\VariableValidatorService $validatorService
     */
    public function __construct(
        ConnectionInterface $connection,
        EggRepositoryInterface $eggRepository,
        EnvironmentService $environmentService,
        ServerRepositoryInterface $repository,
        ServerConfigurationStructureService $structureService,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService
    ) {
        $this->connection = $connection;
        $this->eggRepository = $eggRepository;
        $this->environmentService = $environmentService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
        $this->structureService = $structureService;
    }

    /**
     * Process startup modification for a server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array $data
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, array $data): Server
    {
        $this->connection->beginTransaction();
        if (! is_null(array_get($data, 'environment'))) {
            $this->validatorService->setUserLevel($this->getUserLevel());
            $results = $this->validatorService->handle(array_get($data, 'egg_id', $server->egg_id), array_get($data, 'environment', []));

            $results->each(function ($result) use ($server) {
                $this->serverVariableRepository->withoutFreshModel()->updateOrCreate([
                    'server_id' => $server->id,
                    'variable_id' => $result->id,
                ], [
                    'variable_value' => $result->value ?? '',
                ]);
            });
        }

        if ($this->isUserLevel(User::USER_LEVEL_ADMIN)) {
            $this->updateAdministrativeSettings($data, $server);
        }

        $this->connection->commit();

        return $server;
    }

    /**
     * Update certain administrative settings for a server in the DB.
     *
     * @param array $data
     * @param \Pterodactyl\Models\Server $server
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function updateAdministrativeSettings(array $data, Server &$server)
    {
        if (
            is_digit(array_get($data, 'egg_id'))
            && $data['egg_id'] != $server->egg_id
            && is_null(array_get($data, 'nest_id'))
        ) {
            $egg = $this->eggRepository->setColumns(['id', 'nest_id'])->find($data['egg_id']);
            $data['nest_id'] = $egg->nest_id;
        }

        $server = $this->repository->update($server->id, [
            'installed' => 0,
            'startup' => array_get($data, 'startup', $server->startup),
            'nest_id' => array_get($data, 'nest_id', $server->nest_id),
            'egg_id' => array_get($data, 'egg_id', $server->egg_id),
            'pack_id' => array_get($data, 'pack_id', $server->pack_id) > 0 ? array_get($data, 'pack_id', $server->pack_id) : null,
            'skip_scripts' => array_get($data, 'skip_scripts') ?? isset($data['skip_scripts']),
            'image' => array_get($data, 'docker_image', $server->image),
        ]);
    }
}
