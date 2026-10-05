<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Concerns\FormatsActivityLogs;

#[ResponseField('ip', example: '192.0.2.10', nullable: true)]
#[ResponseField('description', example: 'Changed account email address.', nullable: true)]
#[ResponseField('properties', schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'integer'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'array', 'items' => ['type' => 'string']], ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]], 'example' => ['old' => 'old-user@example.com', 'new' => 'new-user@example.com']])]
class ActivityLogTransformer extends BaseAdminTransformer
{
    use FormatsActivityLogs;

    protected array $includeRelations = [
        'actor' => ['relation' => 'actor', 'transformer' => UserTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['actor'];

    public function getResourceName(): string
    {
        return ActivityLog::RESOURCE_NAME;
    }

    /**
     * Reaching this transformer already requires an administrative read permission, so
     * the originating address is never masked the way it is on the client API.
     *
     * @return array{id: string, batch: string|null, event: string, is_api: bool, ip: string|null, description: string|null, properties: object, has_additional_metadata: bool, timestamp: string}
     */
    public function transform(ActivityLog $model): array
    {
        return $this->activityAttributes($model, true);
    }

    public function includeActor(ActivityLog $model): NullResource|Item
    {
        if (! $model->actor instanceof User) {
            return $this->null();
        }

        return $this->item($model->actor, $this->makeTransformer(UserTransformer::class), User::RESOURCE_NAME);
    }
}
