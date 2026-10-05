<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Support\JsonValueGuard;

class ServerVariableTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /** The server variables relation resolves to EggVariable models carrying the server-specific value. */
    /**
     * @return ApiPayload
     */
    public function transform(EggVariable $variable): array
    {
        $attributes = $variable->attributesToArray();
        JsonValueGuard::assertPayload($attributes);

        return $attributes;
    }
}
