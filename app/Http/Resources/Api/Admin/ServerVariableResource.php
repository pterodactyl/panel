<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\ServerVariable;

/**
 * @extends AdminResource<EggVariable>
 */
class ServerVariableResource extends AdminResource
{
    public function resourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /**
     * The server variables relation resolves to EggVariable models carrying the server-specific value.
     */
    public function toArray(Request $request): array
    {
        return $this->resource->attributesToArray();
    }
}
