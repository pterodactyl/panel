<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;
use UnexpectedValueException;

class SetDockerImageRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::StartupDockerImage->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        if (! $this->filled('docker_image')) {
            return [
                'docker_image' => ['required', 'string', 'max:191'],
            ];
        }

        $server = $this->parameter('server', Server::class);
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');

        return [
            'docker_image' => ['required', 'string', 'max:191', 'regex:/^[\w#\.\/\- ]*\|?~?[\w\.\/\-:@ ]*$/', Rule::in(array_values($egg->docker_images))],
        ];
    }
}
