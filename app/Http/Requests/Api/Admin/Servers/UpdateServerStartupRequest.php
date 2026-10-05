<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Concerns\ParsesServerStartupData;
use Pterodactyl\Models\Server;
use Pterodactyl\Validation\ServerRules;

class UpdateServerStartupRequest extends ServerWriteRequest
{
    use ParsesServerStartupData;

    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for updating a server's startup configuration.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $data = ServerRules::rules($this->parameter('server', Server::class));

        return [
            'startup' => $data['startup'],
            'environment' => ['present', 'array'],
            'egg_id' => $data['egg_id'],
            'docker_image' => $data['image'],
            'skip_scripts' => ['present', 'boolean'],
        ];
    }

    /**
     * Normalize the validated data for the service.
     *
     * @return ServerStartupModificationData
     */
    public function payload(): array
    {
        return $this->serverStartupData('egg_id', 'docker_image');
    }
}
