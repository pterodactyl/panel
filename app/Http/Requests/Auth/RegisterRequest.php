<?php

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorized(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user' => 'required|string|min:3',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }
}
