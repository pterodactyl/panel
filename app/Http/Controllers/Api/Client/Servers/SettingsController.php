<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\ReinstallsServers;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Contracts\Servers\UpdatesServerDockerImage;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Settings\ReinstallServerRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Settings\RenameServerRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Settings\SetDockerImageRequest;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;
use UnexpectedValueException;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Settings', 'Rename, reinstall, and update Docker image settings for an accessible server.')]
class SettingsController extends ClientApiController
{
    private const array BAD_REQUEST_ERROR = [
        'errors' => [
            [
                'code' => 'BadRequestHttpException',
                'status' => '400',
                'detail' => "This server's Docker image has been manually set by an administrator and cannot be updated.",
            ],
        ],
    ];

    /**
     * Renames a server.
     *
     * @throws Throwable
     */
    #[Endpoint('Rename server', 'Updates the server name and optionally its description.')]
    #[ScribeResponse(status: 204, description: 'Server renamed.')]
    public function rename(RenameServerRequest $request, UpdatesServerDetails $details, Server $server): JsonResponse
    {
        // The action replaces the owner, external ID and description too, so pass the
        // current values through; the name, and a description if sent, come from the request.
        $details->update($server, [
            'external_id' => $server->external_id,
            'owner_id' => $server->owner_id,
            'description' => $server->description,
            ...$request->actionInput(),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Reinstalls the server on the daemon.
     *
     * @throws Throwable
     */
    #[Endpoint('Reinstall server', 'Queues a reinstall of the server on Wings.')]
    #[ScribeResponse(status: 202, description: 'Server reinstall queued.')]
    public function reinstall(ReinstallServerRequest $request, ReinstallsServers $reinstall, Server $server): JsonResponse
    {
        $reinstall->reinstall($server);

        return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    /**
     * Changes the Docker image in use by the server.
     *
     * @throws Throwable
     */
    #[Endpoint('Set server Docker image', 'Updates the server Docker image to one of the images allowed by the server egg.')]
    #[BodyParam('docker_image', 'string', 'The Docker image to use.', required: true, example: 'ghcr.io/pterodactyl/yolks:java_21')]
    #[ScribeResponse(status: 204, description: 'Docker image updated.')]
    #[ScribeResponse(self::BAD_REQUEST_ERROR, status: 400, description: 'The server image was manually set by an administrator and cannot be changed through this endpoint.')]
    public function dockerImage(SetDockerImageRequest $request, UpdatesServerDockerImage $dockerImage, Server $server): JsonResponse
    {
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');
        throw_unless(in_array($server->image, $egg->docker_images, true), BadRequestHttpException::class, "This server's Docker image has been manually set by an administrator and cannot be updated.");

        $dockerImage->update($server, $request->string('docker_image')->toString());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
