<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Users;

use Illuminate\Support\Arr;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Support\ValidationRuleSubset;
use Pterodactyl\Validation\UserRules;

class StoreUserRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminUsersCreate];
    }

    /**
     * {@inheritdoc}
     */
    public function extensionForm(): string
    {
        return 'admin.user';
    }

    /**
     * Validation rules for creating a user.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        $rules ??= UserRules::rules();

        $response = ValidationRuleSubset::select($rules, [
            'external_id',
            'email',
            'username',
            'password',
            'root_admin',
            'name_first',
            'name_last',
        ]);

        // Nested on input; remapped onto the flat `language` column in validated().
        $response['preferences.language'] = $rules['language'];

        return $response;
    }

    /**
     * @return UserCreationData
     */
    public function payload(): array
    {
        $validated = parent::validated();

        $data = [
            'email' => JsonValueGuard::string(Arr::get($validated, 'email')),
            'username' => JsonValueGuard::string(Arr::get($validated, 'username')),
            'name_first' => JsonValueGuard::string(Arr::get($validated, 'name_first')),
            'name_last' => JsonValueGuard::string(Arr::get($validated, 'name_last')),
        ];

        foreach (['external_id', 'password'] as $nullable) {
            if (array_key_exists($nullable, $validated)) {
                $value = Arr::get($validated, $nullable);
                $data[$nullable] = $value !== null && $value !== '' ? JsonValueGuard::string($value) : null;
            }
        }

        if (array_key_exists('root_admin', $validated)) {
            $data['root_admin'] = JsonValueGuard::boolean(Arr::get($validated, 'root_admin'));
        }

        $language = Arr::get($validated, 'preferences.language');
        if ($language !== null && $language !== '') {
            $data['language'] = JsonValueGuard::string($language);
        }

        return $data;
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'external_id' => 'Third Party Identifier',
            'name_first' => 'First Name',
            'name_last' => 'Last Name',
            'root_admin' => 'Root Administrator Status',
        ];
    }
}
