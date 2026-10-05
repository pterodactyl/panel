<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Base;

use Illuminate\Foundation\Http\FormRequest;

class LocaleRequest extends FormRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'regex:/^[a-z][a-z]$/'],
            'namespace' => ['required', 'string', 'regex:/^(?:[a-z]{1,191}|ext-[a-z][a-z0-9-]{0,47}::[a-z][a-z0-9_-]{0,63})$/'],
        ];
    }
}
