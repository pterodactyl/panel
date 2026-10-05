<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseField;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Requests\Api\Client\GetExtensionProgressRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Extensions\ExtensionJobProgress;
use Pterodactyl\Services\Extensions\ExtensionRepository;

#[Group('Client API')]
#[Subgroup('Extension progress')]
#[ResponseField('data', 'object', 'Latest progress snapshot.')]
#[ResponseField('data.id', 'string', 'Progress job UUID.')]
#[ResponseField('data.extension', 'string', 'Extension identifier.')]
#[ResponseField('data.percent', 'integer', 'Progress from zero to one hundred.')]
#[ResponseField('data.message', 'string', 'Current progress message.')]
#[ResponseField('data.sequence', 'integer', 'Monotonically increasing update sequence.')]
#[ResponseField('data.updated_at', 'string', 'Update timestamp in ISO 8601 format.')]
class ExtensionProgressController extends ClientApiController
{
    public const array EXAMPLE = ['data' => ['id' => '11111111-1111-4111-8111-111111111111', 'extension' => 'probe', 'status' => 'running', 'percent' => 25, 'message' => 'Working', 'sequence' => 2, 'updated_at' => '2026-10-02T00:00:00+00:00']];

    #[Endpoint('Get extension job progress', 'Returns the latest user-scoped progress snapshot, including after reconnect. Expired, disabled, and inaccessible jobs return 404.')]
    #[ScribeResponse(self::EXAMPLE)]
    #[ResponseField('data.status', 'string', 'Job state.', enum: ['running', 'completed', 'failed'])]
    public function user(GetExtensionProgressRequest $request, ExtensionJobProgress $progress, ExtensionRepository $extensions, string $extension, string $job): JsonResponse
    {
        abort_unless(config('extensions.enabled') && $extensions->enabled()->has($extension), 404);

        return new JsonResponse(['data' => $progress->visible($extension, $job, $this->authenticatedUser($request))->payload()], headers: ['Cache-Control' => 'no-store']);
    }

    #[Endpoint('Get server extension job progress', 'Returns the latest server-scoped snapshot to its creator or users holding the operation permission. Expired, disabled, and inaccessible jobs return 404.')]
    #[ScribeResponse(self::EXAMPLE)]
    #[ResponseField('data.status', 'string', 'Job state.', enum: ['running', 'completed', 'failed'])]
    public function server(GetExtensionProgressRequest $request, ExtensionJobProgress $progress, ExtensionRepository $extensions, Server $server, string $extension, string $job): JsonResponse
    {
        abort_unless(config('extensions.enabled') && $extensions->enabled()->has($extension), 404);

        return new JsonResponse(['data' => $progress->visible($extension, $job, $this->authenticatedUser($request), $server)->payload()], headers: ['Cache-Control' => 'no-store']);
    }
}
