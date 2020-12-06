<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;

class UpdatePasswordRequest extends ClientApiRequest
{
    /**
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        // Verify password matches when changing password or email.
        if (! password_verify($this->input('current_password'), $this->user()->password)) {
            throw new InvalidPasswordProvidedException(trans('validation.internal.invalid_password'));
        }

        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
