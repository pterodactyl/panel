<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client;

class GetActivityLogsRequest extends ClientApiRequest
{
    /**
     * The filters and pagination an account's or a server's activity can be read with.
     *
     * @return ValidationRules
     */
    public function rules(): array
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
