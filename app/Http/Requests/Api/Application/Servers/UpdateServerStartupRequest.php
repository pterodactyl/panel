<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Http\Requests\Concerns\ParsesServerStartupData;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Validation\ServerRules;

class UpdateServerStartupRequest extends ApplicationApiRequest
{
    use ParsesServerStartupData;

    protected ?string $resource = AdminAcl::RESOURCE_SERVERS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Validation rules to run the input against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $data = ServerRules::rules($this->parameter('server', Server::class));

        return [
            'startup' => $data['startup'],
            'environment' => ['present', 'array'],
            'egg' => $data['egg_id'],
            'image' => $data['image'],
            'skip_scripts' => ['present', 'boolean'],
        ];
    }

    /**
     * Return the validated data in a format that is expected by the service.
     *
     * @return ServerStartupModificationData
     */
    public function payload(): array
    {
        return $this->serverStartupData('egg', 'image');
    }
}
