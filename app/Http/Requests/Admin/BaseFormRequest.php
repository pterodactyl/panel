<?php

namespace Pterodactyl\Http\Requests\Admin;

class BaseFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'company' => 'required|between:1,256',
        ];
    }
}
