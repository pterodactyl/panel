<?php

namespace App\Services\Servers;

use Illuminate\Support\Arr;
use App\Models\User;
use App\Models\Server;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Traits\Services\HasUserLevels;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Contracts\Repository\ServerVariableRepositoryInterface;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class StartupModificationService
{
    use HasUserLevels;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    private $eggRepository;

    /**
     * @var \App\Services\Servers\EnvironmentService
     */
    private $environmentService;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Contracts\Repository\ServerVariableRepositoryInterface
     */
    private $serverVariableRepository;

    /**
     * @var \App\Services\Servers\VariableValidatorService
     */
    private $validatorService;

    /**
     * StartupModificationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                            $connection
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface  $daemonServerRepository
     * @param \App\Contracts\Repository\EggRepositoryInterface            $eggRepository
     * @param \App\Services\Servers\EnvironmentService                    $environmentService
     * @param \App\Contracts\Repository\ServerRepositoryInterface         $repository
     * @param \App\Contracts\Repository\ServerVariableRepositoryInterface $serverVariableRepository
     * @param \App\Services\Servers\VariableValidatorService              $validatorService
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepositoryInterface $daemonServerRepository,
        EggRepositoryInterface $eggRepository,
        EnvironmentService $environmentService,
        ServerRepositoryInterface $repository,
        ServerVariableRepositoryInterface $serverVariableRepository,
        VariableValidatorService $validatorService
    ) {
        $this->daemonServerRepository = $daemonServerRepository;
        $this->connection = $connection;
        $this->eggRepository = $eggRepository;
        $this->environmentService = $environmentService;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->validatorService = $validatorService;
    }

    /**
     * Process startup modification for a server.
     *
     * @param \App\Models\Server $server
     * @param array                      $data
     * @return \App\Models\Server
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, array $data): Server
    {
        $this->connection->beginTransaction();
        if (! is_null(Arr::get($data, 'environment'))) {
            $this->validatorService->setUserLevel($this->getUserLevel());
            $results = $this->validatorService->handle(Arr::get($data, 'egg_id', $server->egg_id), Arr::get($data, 'environment', []));

            $results->each(function ($result) use ($server) {
                $this->serverVariableRepository->withoutFreshModel()->updateOrCreate([
                    'server_id' => $server->id,
                    'variable_id' => $result->id,
                ], [
                    'variable_value' => $result->value ?? '',
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

        return $server;
    }

    /**
     * Update certain administrative settings for a server in the DB.
     *
     * @param array                      $data
     * @param \App\Models\Server $server
     * @param array                      $daemonData
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    private function updateAdministrativeSettings(array $data, Server &$server, array &$daemonData)
    {
        if (
            is_digit(Arr::get($data, 'egg_id'))
            && $data['egg_id'] != $server->egg_id
            && is_null(Arr::get($data, 'nest_id'))
        ) {
            $egg = $this->eggRepository->setColumns(['id', 'nest_id'])->find($data['egg_id']);
            $data['nest_id'] = $egg->nest_id;
        }

        $server = $this->repository->update($server->id, [
            'installed' => 0,
            'startup' => Arr::get($data, 'startup', $server->startup),
            'nest_id' => Arr::get($data, 'nest_id', $server->nest_id),
            'egg_id' => Arr::get($data, 'egg_id', $server->egg_id),
            'pack_id' => Arr::get($data, 'pack_id', $server->pack_id) > 0 ? Arr::get($data, 'pack_id', $server->pack_id) : null,
            'skip_scripts' => Arr::get($data, 'skip_scripts') ?? isset($data['skip_scripts']),
            'image' => Arr::get($data, 'docker_image', $server->image),
        ]);

        $daemonData = array_merge($daemonData, [
            'build' => ['image' => $server->image],
            'service' => array_merge(
                $this->repository->getDaemonServiceData($server, true),
                ['skip_scripts' => $server->skip_scripts]
            ),
        ]);
    }
}
