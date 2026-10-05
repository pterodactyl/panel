<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Pterodactyl\Models\User;
use Pterodactyl\Rules\UserEmail;
use Pterodactyl\Rules\Username;

final class UserRules
{
    /**
     * Rules every new user password must pass, wherever it is set.
     *
     * @var list<string>
     */
    public const array PASSWORD = ['string', 'min:8'];

    /**
     * Validation rules for the user requests and p:user:make.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?User $ignore = null): array
    {
        return [
            'email' => ['required', 'email:strict', 'between:1,191', new UserEmail, Rule::unique('users', 'email')->ignore($ignore)],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:191', Rule::unique('users', 'external_id')->ignore($ignore)],
            'username' => ['required', 'between:1,191', Rule::unique('users', 'username')->ignore($ignore), new Username],
            'name_first' => ['required', 'string', 'between:1,191'],
            'name_last' => ['required', 'string', 'between:1,191'],
            'password' => ['sometimes', 'nullable', ...self::PASSWORD],
            'root_admin' => ['boolean'],
            'language' => ['string', new In(array_keys((new User)->getAvailableLanguages()))],
        ];
    }
}
