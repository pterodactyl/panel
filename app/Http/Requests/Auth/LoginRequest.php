<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'user' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
