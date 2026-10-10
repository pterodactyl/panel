<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Validation\UserRules;

class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && (! $this->parameter('user', User::class)->root_admin || $this->canManageAdministrators());
    }

    /**
     * Return the validation rules for this request.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        return parent::rules(UserRules::rules($this->parameter('user', User::class)));
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): User
    {
        return $this->parameter('user', User::class);
    }
}
