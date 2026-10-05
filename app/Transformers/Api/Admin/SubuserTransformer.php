<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\Subuser;

class SubuserTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Subuser $subuser): array
    {
        return [
            'id' => $subuser->id,
            'user_id' => $subuser->user_id,
            'server_id' => $subuser->server_id,
            'permissions' => $subuser->permissions,
            'created_at' => $this->formatTimestamp($subuser->created_at),
            'updated_at' => $this->formatTimestamp($subuser->updated_at),
        ];
    }
}
