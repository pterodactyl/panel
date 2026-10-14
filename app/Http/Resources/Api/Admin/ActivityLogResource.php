<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Concerns\FormatsActivityLogs;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;

/**
 * @extends AdminResource<ActivityLog>
 */
#[ResponseField('ip', example: '192.0.2.10', nullable: true)]
#[ResponseField('description', example: 'Changed account email address.', nullable: true)]
#[ResponseField('properties', schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'integer'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'array', 'items' => ['type' => 'string']], ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]], 'example' => ['old' => 'old-user@example.com', 'new' => 'new-user@example.com']])]
class ActivityLogResource extends AdminResource
{
    use FormatsActivityLogs;

    protected array $includeRelations = [
        'actor' => ['relation' => 'actor', 'resource' => UserResource::class],
    ];

    protected array $availableIncludes = ['actor'];

    public function resourceName(): string
    {
        return ActivityLog::RESOURCE_NAME;
    }

    /**
     * Reaching this resource already requires an administrative read permission, so the
     * originating address is never masked the way it is on the client API.
     */
    public function toArray(Request $request): array
    {
        return $this->activityAttributes($this->resource, true);
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'actor' => $this->includeActor(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * @return ApiResource<User>|NullResource
     */
    private function includeActor(): ApiResource|NullResource
    {
        if (! $this->resource->actor instanceof User) {
            return $this->null();
        }

        return $this->item($this->resource->actor, UserResource::class, User::RESOURCE_NAME);
    }
}
