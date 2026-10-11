<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Support\Facades\Hash;
use Pterodactyl\Actions\Users\CreateUser;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;

class UpdatePasswordRequest extends ClientApiRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', ...CreateUser::passwordRules()],
        ];
    }

    protected function passedValidation(): void
    {
        throw_unless(Hash::check(JsonValueGuard::string($this->validated('current_password')), $this->user()->password), InvalidPasswordProvidedException::class, trans('validation.internal.invalid_password'));
    }
}
