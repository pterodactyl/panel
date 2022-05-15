<?php

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SftpAuthenticationFormRequest extends FormRequest
{
    /**
     * Authenticate the request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Rules to apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => ['nullable', 'in:password,public_key'],
            'username' => ['required', 'string'],
            'password' => [
                Rule::when(fn () => $this->input('type') !== 'public_key', ['required', 'string'], ['nullable']),
            ],
        ];
    }

    /**
     * Return only the fields that we are interested in from the request.
     * This will include empty fields as a null value.
     *
     * @return array
     */
    public function normalize()
    {
        return $this->only(
            array_keys($this->rules())
        );
    }
}
