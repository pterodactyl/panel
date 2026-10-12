<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Actions\Users\CreateUser;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class StoreUserRequest extends ApplicationApiRequest
{
    use ValidatesExtensionFields {
        actionInput as private inputWithExtensions;
    }

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
        $rules ??= CreateUser::rules();

        return [
            'external_id' => $rules['external_id'],
            'email' => $rules['email'],
            'username' => $rules['username'],
            'password' => $rules['password'],
            'language' => $rules['language'],
            'root_admin' => $rules['root_admin'],
            'first_name' => $rules['name_first'],
            'last_name' => $rules['name_last'],
        ];
    }

    /**
     * The validated input under the action's field names: first_name and last_name are
     * its name_first and name_last.
     *
     * @return array<string, mixed>
     */
    public function actionInput(): array
    {
        $input = $this->inputWithExtensions();
        $input['name_first'] = $input['first_name'] ?? null;
        $input['name_last'] = $input['last_name'] ?? null;
        unset($input['first_name'], $input['last_name']);

        return $input;
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

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return User::class;
    }
}
