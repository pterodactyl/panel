<?php

namespace Pterodactyl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class FrontendUserFormRequest extends FormRequest
{
    abstract public function rules();

    /**
     * Determine if a user is authorized to access this endpoint.
     *
     * @return bool
     */
    public function authorize()
    {
        return !is_null($this->user());
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
