<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Support\Facades\Hash;
use Pterodactyl\Actions\Users\CreateUser;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class UpdateEmailRequest extends ClientApiRequest
{
    /**
     * The password is checked before validation runs, so the unique email rule cannot
     * tell someone without it which addresses already belong to an account. A missing
     * or malformed password is left to the rules below.
     */
    public function authorize(): bool
    {
        $password = $this->input('password');

        throw_if(is_string($password) && ! Hash::check($password, $this->user()->password), InvalidPasswordProvidedException::class, trans('validation.internal.invalid_password'));

        return parent::authorize();
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'email' => CreateUser::rules($this->user())['email'],
            'password' => ['required', 'string'],
        ];
    }
}
