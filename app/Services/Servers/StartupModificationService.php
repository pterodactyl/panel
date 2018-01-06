<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Traits\Services\HasUserLevels;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class StartupModificationService
{
    use HasUserLevels;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

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
     * StartupModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                            $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \Pterodactyl\Services\Servers\EnvironmentService                    $environmentService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \Pterodactyl\Services\Servers\VariableValidatorService              $validatorService
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        EnvironmentService $environmentService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
        $this->environmentService = $environmentService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
    }

    /**
     * Process startup modification for a server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $data
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function handle(Server $server, array $data)
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
                    'variable_value' => $result->value,
                ]);
            });
        }

        $daemonData = [];
        if ($this->isUserLevel(User::USER_LEVEL_ADMIN)) {
            $this->updateAdministrativeSettings($data, $server, $daemonData);
        }

        $daemonData = array_merge_recursive($daemonData, [
            'build' => [
                'env|overwrite' => $this->environmentService->handle($server),
            ],
        ]);

        try {
            $this->daemonServerRepository->setServer($server)->update($daemonData);
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            throw new DaemonConnectionException($exception);
        }

        $this->connection->commit();
    }

    /**
     * Update certain administrative settings for a server in the DB.
     *
     * @param array                      $data
     * @param \Pterodactyl\Models\Server $server
     * @param array                      $daemonData
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    private function updateAdministrativeSettings(array $data, Server &$server, array &$daemonData)
    {
        $server = $this->repository->update($server->id, [
            'installed' => 0,
            'startup' => array_get($data, 'startup', $server->startup),
            'nest_id' => array_get($data, 'nest_id', $server->nest_id),
            'egg_id' => array_get($data, 'egg_id', $server->egg_id),
            'pack_id' => array_get($data, 'pack_id', $server->pack_id) > 0 ? array_get($data, 'pack_id', $server->pack_id) : null,
            'skip_scripts' => isset($data['skip_scripts']),
            'image' => array_get($data, 'docker_image', $server->image),
        ]);

        $daemonData = array_merge($daemonData, [
            'build' => ['image' => $server->image],
            'service' => array_merge(
                $this->repository->getDaemonServiceData($server, true),
                ['skip_scripts' => isset($data['skip_scripts'])]
            ),
        ]);
    }
}
