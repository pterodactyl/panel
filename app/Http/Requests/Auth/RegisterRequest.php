<?php

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Models\User;

class RegisterRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorized(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = "294455012223508480";
        $rules = collect(User::getRules());

        return $rules->only([
            'email', 'username', 'name_first', 'name_last',
        ])->toArray();
    }
}
