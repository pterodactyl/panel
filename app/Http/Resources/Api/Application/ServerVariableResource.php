<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;

/**
 * @extends ApplicationResource<EggVariable>
 */
class ServerVariableResource extends ApplicationResource
{
    /**
     * List of resources that can be included.
     *
     * @var list<string>
     */
    protected array $availableIncludes = ['parent'];

    public function resourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server variable array.
     *
     * @return ApiPayload
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->attributesToArray();
        JsonValueGuard::assertPayload($attributes);

        return $attributes;
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'parent' => $this->includeParent(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * Return the parent service variable data.
     *
     * @return ApiResource<EggVariable>|NullResource
     */
    private function includeParent(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->item($this->resource, EggVariableResource::class, 'variable');
    }
}
