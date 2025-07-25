<?php

namespace Pterodactyl\Http\Requests\Base;

use Illuminate\Foundation\Http\FormRequest;

class LocaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'regex:/^[a-z][a-z]$/'],
            'namespace' => ['required', 'string', 'regex:/^[a-z]{1,191}$/'],
        ];
    }
}
