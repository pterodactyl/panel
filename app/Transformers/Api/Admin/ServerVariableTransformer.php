<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\ServerVariable;

class ServerVariableTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /** The server variables relation resolves to EggVariable models carrying the server-specific value. */
    /**
     * @return array<string, mixed>
     */
    public function transform(EggVariable $variable): array
    {
        return $variable->attributesToArray();
    }
}
