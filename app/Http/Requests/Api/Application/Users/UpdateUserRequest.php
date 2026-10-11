<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Actions\Users\CreateUser;
use Pterodactyl\Models\User;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Return the validation rules for this request.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        return parent::rules(CreateUser::rules($this->parameter('user', User::class)));
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): User
    {
        return $this->parameter('user', User::class);
    }
}
