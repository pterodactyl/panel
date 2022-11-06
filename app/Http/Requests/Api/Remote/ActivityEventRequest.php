<?php

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Support\Collection;
use Illuminate\Foundation\Http\FormRequest;

class ActivityEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.*' => ['array'],
            'data.*.user' => ['sometimes', 'nullable', 'uuid'],
            'data.*.server' => ['required', 'uuid'],
            'data.*.event' => ['required', 'string'],
            'data.*.metadata' => ['present', 'nullable', 'array'],
            'data.*.ip' => ['sometimes', 'nullable', 'ip'],
            'data.*.timestamp' => ['required', 'string'],
        ];
    }

    /**
     * Returns all the unique server UUIDs that were received in this request.
     */
    public function servers(): array
    {
        return Collection::make($this->input('data'))->pluck('server')->unique()->toArray();
    }

    /**
     * Returns all the unique user UUIDs that were submitted in this request.
     */
    public function users(): array
    {
        return Collection::make($this->input('data'))
            ->filter(function ($value) {
                return !empty($value['user']);
            })
            ->pluck('user')
            ->unique()
            ->toArray();
    }
}
