<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServerInstallService
{
    /**
     * @return array{container_image: string|null, entrypoint: string|null, script: string|null}
     */
    public function info(Request $request, string $uuid): array
    {
        $server = $this->resolveForNode($request, $uuid);
        $egg = $server->egg;
        throw_unless($egg instanceof Egg, NotFoundHttpException::class, 'The requested server does not have an egg assigned.');

        return [
            'container_image' => $egg->copy_script_container,
            'entrypoint' => $egg->copy_script_entry,
            'script' => $egg->copy_script_install,
        ];
    }

    public function resolveForNode(Request $request, string $uuid): Server
    {
        $server = Server::query()->with('node')->whereUuidOrShort($uuid)->firstOrFail();

        throw_unless($server->node->is(RemoteRequestNode::get($request)), HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        return $server;
    }
}
