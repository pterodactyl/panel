<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ActivityLog;

class ActivityLogTransformer extends BaseClientTransformer
{
    protected array $availableIncludes = ['actor'];

    public function getResourceName(): string
    {
        return ActivityLog::RESOURCE_NAME;
    }

    public function transform(ActivityLog $model): array
    {
        return [
            'batch' => $model->batch,
            'event' => $model->event,
            'ip' => $model->ip,
            'description' => $model->description,
            'properties' => $model->properties ? $model->properties->toArray() : [],
            'has_additional_metadata' => $this->hasAdditionalMetadata($model),
            'timestamp' => $model->timestamp->toIso8601String(),
        ];
    }

    public function includeActor(ActivityLog $model)
    {
        if (!$model->actor instanceof User) {
            return $this->null();
        }

        return $this->item($model->actor, $this->makeTransformer(UserTransformer::class), User::RESOURCE_NAME);
    }

    /**
     * Determines if there are any log properties that we've not already exposed
     * in the response language string and that are not just the IP address or
     * the browser useragent.
     *
     * This is used by the front-end to selectively display an "additional metadata"
     * button that is pointless if there is nothing the user can't already see from
     * the event description.
     */
    protected function hasAdditionalMetadata(ActivityLog $model): bool
    {
        if (is_null($model->properties) || $model->properties->isEmpty()) {
            return false;
        }

        $str = trans('activity.' . str_replace(':', '.', $model->event));
        preg_match_all('/:(?<key>[\w-]+)(?:\W?|$)/', $str, $matches);

        $exclude = array_merge($matches['key'], ['ip', 'useragent']);
        foreach ($model->properties->keys() as $key) {
            if (!in_array($key, $exclude, true)) {
                return true;
            }
        }

        return false;
    }
}
