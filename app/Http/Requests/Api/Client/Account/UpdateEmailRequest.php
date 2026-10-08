<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\UserRules;

class UpdateEmailRequest extends ClientApiRequest
{
    /**
     * Check if the request is authorised.
     *
     * Need valid password before checking emails otherwise it allows email enumeration.
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $validator = Validator::make($this->all(), Arr::only($this->rules(), ['password']), $this->messages(), $this->attributes());
        if ($validator->fails()) {
            $this->failedValidation($validator);
        }

        throw_unless(Hash::check(JsonValueGuard::string($this->input('password')), $this->user()->password), InvalidPasswordProvidedException::class, trans('validation.internal.invalid_password'));

        return true;
    }

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
}
