<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\AttachesMountsToServers;
use Pterodactyl\Contracts\Servers\DetachesMountsFromServers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Controllers\Api\Admin\Mounts\MountController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\DeleteServerMountRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServerMountsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\StoreServerMountRequest;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\MountListService;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Admin\MountTransformer;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Server Mounts', 'Attach and detach eligible mount definitions for a server.')]
class ServerMountController extends AdminApiController
{
    private const array SERVER_MOUNT_EXAMPLE = [
        'object' => 'mount',
        'attributes' => [
            ...MountController::MOUNT_EXAMPLE['attributes'],
            'mounted' => true,
        ],
    ];

    private const array SERVER_MOUNT_LIST_EXAMPLE = [
        'object' => 'list',
        'data' => [
            self::SERVER_MOUNT_EXAMPLE,
        ],
    ];

    private const array INSTALL_STATE_ERROR = [
        'errors' => [
            [
                'code' => 'HttpException',
                'status' => '403',
                'detail' => 'Access to this resource is not allowed due to the current installation state.',
            ],
        ],
    ];

    /**
     * List server mounts.
     *
     * @return array{object: string, data: list<array{object: string, attributes: array<string, mixed>}>}
     */
    #[Endpoint('List server mounts', 'Returns mount definitions that are eligible for a server, including whether each mount is attached.')]
    #[ScribeResponse(self::SERVER_MOUNT_LIST_EXAMPLE, description: 'Eligible server mounts returned.')]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function index(GetServerMountsRequest $request, MountListService $listing, Server $server): array
    {
        $this->assertServerInstalled($server);

        $mounts = $listing->handle($server);

        $mountedIds = $server->mounts->pluck('id')->all();

        $transformer = $this->getTransformer(MountTransformer::class);

        $data = array_values($mounts->map(fn (Mount $mount): array => array_merge($transformer->transform($mount), [
            'mounted' => in_array($mount->id, $mountedIds, true),
        ]))->all());

        return [
            'object' => 'list',
            'data' => array_map(fn (array $attributes): array => [
                'object' => Mount::RESOURCE_NAME,
                'attributes' => $attributes,
            ], $data),
        ];
    }

    /**
     * Create server mount.
     */
    #[Endpoint('Attach server mount', 'Attaches an eligible mount definition to a server.')]
    #[ScribeResponse(status: 204, description: 'Mount attached.')]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function store(StoreServerMountRequest $request, AttachesMountsToServers $attach, Server $server): Response
    {
        $this->assertServerInstalled($server);

        $mount = $attach->attach($server, JsonValueGuard::integer($request->validated('mount_id')));

        Activity::event('admin:server.mount')
            ->subject($server, $mount)
            ->property('name', $mount->name)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Delete server mount.
     */
    #[Endpoint('Detach server mount', 'Detaches a mount definition from a server.')]
    #[ScribeResponse(status: 204, description: 'Mount detached.')]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function destroy(DeleteServerMountRequest $request, DetachesMountsFromServers $detach, Server $server, Mount $mount): Response
    {
        $this->assertServerInstalled($server);

        $detach->detach($server, $mount);

        Activity::event('admin:server.unmount')
            ->subject($server, $mount)
            ->property('name', $mount->name)
            ->log();

        return $this->returnNoContent();
    }

    private function assertServerInstalled(Server $server): void
    {
        throw_unless($server->isInstalled(), HttpException::class, Response::HTTP_FORBIDDEN, 'Access to this resource is not allowed due to the current installation state.');
    }
}
