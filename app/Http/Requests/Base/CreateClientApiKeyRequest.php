<?php

namespace App\Http\Requests\Base;

use App\Http\Requests\FrontendUserFormRequest;

class CreateClientApiKeyRequest extends FrontendUserFormRequest
{
    /**
     * Validate the data being provided.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'memo' => 'required|string|max:255',
            'allowed_ips' => 'nullable|string',
        ];
    }
}
