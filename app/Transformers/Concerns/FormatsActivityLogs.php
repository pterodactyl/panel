<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Concerns;

use Illuminate\Support\Str;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Support\JsonValueTree;
use stdClass;

trait FormatsActivityLogs
{
    /**
     * @return array{id: string, batch: string|null, event: string, is_api: bool, ip: string|null, description: string|null, properties: object, has_additional_metadata: bool, timestamp: string}
     */
    protected function activityAttributes(ActivityLog $model, bool $canViewIp): array
    {
        return [
            // This is not for security, it is only to provide a unique identifier to
            // the front-end for each entry to improve rendering performance since there
            // is nothing else sufficiently unique to key off at this point.
            'id' => sha1((string) $model->id),
            'batch' => $model->batch,
            'event' => $model->event,
            'is_api' => ($model->api_key_id ?? 0) > 0,
            'ip' => $canViewIp ? $model->ip : null,
            'description' => $model->description,
            'properties' => $this->activityProperties($model, $canViewIp),
            'has_additional_metadata' => $this->activityHasAdditionalMetadata($model),
            'timestamp' => $model->timestamp->toAtomString(),
        ];
    }

    /**
     * Transforms any array values in the properties into a countable field for easier
     * use within the translation outputs.
     */
    protected function activityProperties(ActivityLog $model, bool $canViewIp): object
    {
        $values = $model->propertyValues();
        if ($values === []) {
            return new stdClass;
        }

        $properties = collect($values)
            ->mapWithKeys(function (array|bool|float|int|string|null $value, string $key) use ($canViewIp): array {
                if ($key === 'ip' && ! $canViewIp) {
                    return [$key => '[hidden]'];
                }

                $arraySize = JsonValueTree::from($value)->arraySize();
                if ($arraySize === null) {
                    // Perform some directory normalization at this point.
                    if ($key === 'directory') {
                        $value = str_replace('//', '/', '/'.mb_trim(JsonValueGuard::string($value), '/').'/');
                    }

                    return [$key => $value];
                }

                return [$key => $value, "{$key}_count" => $arraySize];
            });

        $keys = $properties->keys()->filter(fn (int|string $key): bool => Str::endsWith((string) $key, '_count'))->values();
        if ($keys->containsOneItem()) {
            $countKey = $keys->first();
            if ($countKey !== null) {
                $properties = $properties->merge(['count' => $properties->get($countKey)])->except($countKey);
            }
        }

        // SAFETY: JSON object properties are string-keyed, and object casting preserves those keys for response encoding.
        return (object) $properties->all();
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
    protected function activityHasAdditionalMetadata(ActivityLog $model): bool
    {
        $properties = $model->propertyValues();
        if ($properties === []) {
            return false;
        }

        $str = trans('activity.'.str_replace(':', '.', $model->event));
        preg_match_all('/:(?<key>[\w.-]+\w)(?:[^\w:]?|$)/', JsonValueGuard::string($str), $matches);

        $exclude = array_merge($matches['key'], ['ip', 'useragent', 'using_sftp']);
        foreach (array_keys($properties) as $key) {
            if (! in_array($key, $exclude, true)) {
                return true;
            }
        }

        return false;
    }
}
