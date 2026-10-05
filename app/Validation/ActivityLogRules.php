<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class ActivityLogRules
{
    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.user' => ['sometimes', 'nullable', 'string'],
            'filter.user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'filter.event' => ['sometimes', 'nullable', 'string'],
            'filter.event_name' => ['sometimes', 'nullable', 'string'],
            'filter.ip' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
