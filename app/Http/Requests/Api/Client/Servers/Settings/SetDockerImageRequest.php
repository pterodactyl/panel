<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Settings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Validation\Rule;
use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SetDockerImageRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_STARTUP_DOCKER_IMAGE;
    }

    public function rules(): array
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = $this->route()->parameter('server');

        Assert::isInstanceOf($server, Server::class);

        return [
            'docker_image' => ['required', 'string', 'max:191', 'regex:/^[\w#\.\/\- ]*\|*[\w\.\/\-:@ ]*$/', Rule::in(array_values($server->egg->docker_images))],
        ];
    }
}
