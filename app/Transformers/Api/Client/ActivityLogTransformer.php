<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Database\Eloquent\Model;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\ActivityLogSubject;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Concerns\FormatsActivityLogs;

#[ResponseField('ip', example: '192.0.2.10', nullable: true)]
#[ResponseField('description', example: 'Changed account email address.', nullable: true)]
#[ResponseField('properties', schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'integer'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'array', 'items' => ['type' => 'string']], ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]], 'example' => ['old' => 'old-user@example.com', 'new' => 'new-user@example.com']])]
class ActivityLogTransformer extends BaseClientTransformer
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
     * @return array{id: string, batch: string|null, event: string, is_api: bool, ip: string|null, description: string|null, properties: object, has_additional_metadata: bool, timestamp: string}
     */
    public function transform(ActivityLog $model): array
    {
        return $this->activityAttributes($model, $this->canViewIP($model));
    }

    public function includeActor(ActivityLog $model): NullResource|Item
    {
        if (! $model->actor instanceof User) {
            return $this->null();
        }

        return $this->item($model->actor, $this->makeTransformer(UserTransformer::class), User::RESOURCE_NAME);
    }

    /**
     * Determines if the user can view the IP address in the output because they are an
     * administrator, because they are the actor that performed the action, or because
     * the entry has no actor at all and the action was performed against them. That last
     * case covers anonymous attempts such as a failed log in, where the address belongs
     * to whoever made the attempt rather than to an identified user.
     */
    protected function canViewIP(ActivityLog $model): bool
    {
        $user = $this->getUser();
        if ($user->root_admin) {
            return true;
        }

        $actor = $model->actor;
        if ($actor instanceof Model) {
            return $actor->is($user);
        }

        return $model->subjects->contains(
            fn (ActivityLogSubject $subject): bool => $subject->subject_type === $user->getMorphClass()
                && $subject->subject_id === $user->getKey()
        );
    }
}
