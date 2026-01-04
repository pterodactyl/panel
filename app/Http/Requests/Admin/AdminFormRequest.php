<?php

namespace Pterodactyl\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class AdminFormRequest extends FormRequest
{
    /**
     * The rules to apply to the incoming form request.
     */
    abstract public function rules(): array;

    /**
     * Determine if the user is an admin and has permission to access this
     * form controller in the first place.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return (bool) $this->user()->root_admin;
    }

    /**
     * Return only the fields that we are interested in from the request.
     * This will include empty fields as a null value.
     */
    public function normalize(?array $only = null): array
    {
        return $this->only($only ?? array_keys($this->rules()));
    }
}
