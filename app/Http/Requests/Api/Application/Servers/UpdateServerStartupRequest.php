<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateServerStartupRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $data = Server::getRulesForUpdate($this->route()->parameter('server')->id);

        return [
            'startup' => $data['startup'],
            'environment' => 'present|array',
            'egg' => $data['egg_id'],
            'image' => $data['image'],
            'skip_scripts' => 'present|boolean',
        ];
    }

    public function validated(): array
    {
        $data = parent::validated();

        return collect($data)->only(['startup', 'environment', 'skip_scripts'])->merge([
            'egg_id' => array_get($data, 'egg'),
            'docker_image' => array_get($data, 'image'),
        ])->toArray();
    }
}
