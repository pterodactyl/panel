<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\UploadFileRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Nodes\NodeJwt;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Files', 'Browse, read, mutate, archive, and transfer server files through Wings.')]
class FileUploadController extends ClientApiController
{
    private const array SIGNED_URL_EXAMPLE = [
        'object' => 'signed_url',
        'attributes' => [
            'url' => 'https://node.example.test/upload/file?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.example.signature',
        ],
    ];

    /**
     * Returns an url where files can be uploaded to.
     */
    #[Endpoint('Get file upload URL', 'Returns a short-lived signed URL for uploading a file directly to Wings.')]
    #[ScribeResponse(self::SIGNED_URL_EXAMPLE, description: 'Signed upload URL returned.')]
    public function __invoke(UploadFileRequest $request, NodeJwt $jwt, Server $server): JsonResponse
    {
        return new JsonResponse([
            'object' => 'signed_url',
            'attributes' => [
                'url' => $this->getUploadUrl($server, $request->user(), $jwt),
            ],
        ]);
    }

    /**
     * Returns an url where files can be uploaded to.
     */
    protected function getUploadUrl(Server $server, User $user, NodeJwt $jwt): string
    {
        $token = $jwt->issue(
            $server->node,
            JwtScope::FileUpload,
            identifiedBy: $user->id.$server->uuid,
            expiresAt: CarbonImmutable::now()->addMinutes(15),
            user: $user,
            claims: ['server_uuid' => $server->uuid],
        );

        return sprintf(
            '%s/upload/file?token=%s',
            $server->node->getConnectionAddress(),
            $token->toString()
        );
    }
}
