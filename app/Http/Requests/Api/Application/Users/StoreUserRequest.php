<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Illuminate\Support\Arr;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Support\ValidationRuleSubset;
use Pterodactyl\Validation\UserRules;

class StoreUserRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_USERS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Return the validation rules for this request.
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
            'language',
            'root_admin',
        ]);

        $response['first_name'] = $rules['name_first'];
        $response['last_name'] = $rules['name_last'];

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
            'name_first' => JsonValueGuard::string(Arr::get($validated, 'first_name')),
            'name_last' => JsonValueGuard::string(Arr::get($validated, 'last_name')),
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

        $language = Arr::get($validated, 'language');
        if ($language !== null && $language !== '') {
            $data['language'] = JsonValueGuard::string($language);
        }

        return $data;
    }

    /**
     * Rename some fields to be more user friendly.
     */
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
