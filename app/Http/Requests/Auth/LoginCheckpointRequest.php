<?php

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginCheckpointRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules to apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'confirmation_token' => 'required|string',
            'authentication_code' => 'required|numeric',
        ];
    }
}
