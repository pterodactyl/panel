<?php

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;

class InstallationDataRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'successful' => 'present|boolean',
        ];
    }
}
