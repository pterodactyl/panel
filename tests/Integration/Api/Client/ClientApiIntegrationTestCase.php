<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Integration\Api\Client;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Task;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Integration\TestResponse;

abstract class ClientApiIntegrationTestCase extends IntegrationTestCase
{
    use DatabaseTransactions;

    /**
     * Override the default createTestResponse from Illuminate so that we can
     * just dump 500-level errors to the screen in the tests without having
     * to keep re-assigning variables.
     *
     * @param  Response  $response
     * @param  Request  $request
     * @return \Illuminate\Testing\TestResponse
     */
    protected function createTestResponse($response, $request)
    {
        return TestResponse::fromBaseResponse($response, $request);
    }

    /**
     * Returns a link to the specific resource using the client API.
     */
    protected function link(mixed $model, ?string $append = null): string
    {
        switch ($model::class) {
            case Server::class:
                $link = "/api/client/servers/$model->uuid";
                break;
            case Schedule::class:
                $link = "/api/client/servers/{$model->server->uuid}/schedules/$model->id";
                break;
            case Task::class:
                $link = "/api/client/servers/{$model->schedule->server->uuid}/schedules/{$model->schedule->id}/tasks/$model->id";
                break;
            case Allocation::class:
                $link = "/api/client/servers/{$model->server->uuid}/network/allocations/$model->id";
                break;
            case Backup::class:
                $link = "/api/client/servers/{$model->server->uuid}/backups/$model->uuid";
                break;
            default:
                throw new InvalidArgumentException(sprintf('Cannot create link for Model of type %s', class_basename($model)));
        }

        return $link.($append ? '/'.mb_ltrim($append, '/') : '');
    }
}
