<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Support\Facades\Hash;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\UserRules;

class UpdateEmailRequest extends ClientApiRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'email' => UserRules::rules($this->user())['email'],
            'password' => ['required', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        throw_unless(Hash::check(JsonValueGuard::string($this->validated('password')), $this->user()->password), InvalidPasswordProvidedException::class, trans('validation.internal.invalid_password'));
    }
}
