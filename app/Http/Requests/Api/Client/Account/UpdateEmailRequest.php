<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Support\Facades\Hash;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Validation\UserRules;

class UpdateEmailRequest extends ClientApiRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ['password' => ['required', 'string']];

        // Only look at the email once the password checks out, so the unique rule can't be used to probe for accounts.
        if (is_string($this->input('password'))) {
            $rules['email'] = UserRules::rules($this->user())['email'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $password = $this->input('password');

        throw_if(is_string($password) && ! Hash::check($password, $this->user()->password), InvalidPasswordProvidedException::class, trans('validation.internal.invalid_password'));
    }
}
