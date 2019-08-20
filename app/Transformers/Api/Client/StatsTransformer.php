<?php

namespace App\Transformers\Api\Client;

use App\Models\Server;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface;

class StatsTransformer extends BaseClientTransformer
{
    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $repository;

    /**
     * Perform dependency injection.
     *
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $repository
     */
    public function handle(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return 'stats';
    }

    /**
     * Transform stats from the daemon into a result set that can be used in
     * the client API.
     *
     * @param \App\Models\Server $model
     * @return array
     */
    public function transform(Server $model)
    {
        try {
            $stats = $this->repository->setServer($model)->details();
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }

        $object = json_decode($stats->getBody()->getContents());

        return [
            'state' => $this->transformState(object_get($object, 'status', 0)),
            'memory' => [
                'current' => round(object_get($object, 'proc.memory.total', 0) / 1024 / 1024),
                'limit' => floatval($model->memory),
            ],
            'cpu' => [
                'current' => object_get($object, 'proc.cpu.total', 0),
                'cores' => object_get($object, 'proc.cpu.cores', []),
                'limit' => floatval($model->cpu),
            ],
            'disk' => [
                'current' => round(object_get($object, 'proc.disk.used', 0)),
                'limit' => floatval($model->disk),
            ],
        ];
    }

    /**
     * Transform the state returned by the daemon into a human readable string.
     *
     * @param int $state
     * @return string
     */
    private function transformState(int $state): string
    {
        switch ($state) {
            case 1:
                return 'on';
            case 2:
                return 'starting';
            case 3:
                return 'stopping';
            case 0:
            default:
                return 'off';
        }
    }
}
