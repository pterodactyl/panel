<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;

class SftpAuthenticationFormRequest extends FormRequest
{
    /**
     * Authenticate the request.
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
            'type' => ['nullable', 'in:password,public_key'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Return only the fields that we are interested in from the request.
     * This will include empty fields as a null value.
     *
     * @return SftpAuthenticationData
     */
    public function normalize(): array
    {
        return [
            'type' => $this->filled('type') ? $this->string('type')->toString() : null,
            'username' => $this->string('username')->toString(),
            'password' => $this->string('password')->toString(),
        ];
    }
}
