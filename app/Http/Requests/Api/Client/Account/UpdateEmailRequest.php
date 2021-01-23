<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Models\User;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;

class UpdateEmailRequest extends ClientApiRequest
{
    /**
     * @throws \Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException
     */
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        // Verify password matches when changing password or email.
        if (!password_verify($this->input('password'), $this->user()->password)) {
            throw new InvalidPasswordProvidedException(trans('validation.internal.invalid_password'));
        }

        return true;
    }

    public function rules(): array
    {
        $rules = User::getRulesForUpdate($this->user());

        return ['email' => $rules['email']];
    }
}
