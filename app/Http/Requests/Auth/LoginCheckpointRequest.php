<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginCheckpointRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules to apply to the request.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'confirmation_token' => ['required', 'string'],
            'authentication_code' => [
                'nullable',
                'numeric',
                Rule::requiredIf(fn (): bool => empty($this->input('recovery_token'))),
            ],
            'recovery_token' => [
                'nullable',
                'string',
                Rule::requiredIf(fn (): bool => empty($this->input('authentication_code'))),
            ],
        ];
    }
}
