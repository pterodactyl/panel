<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Data\ValidatedEggVariable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\User;

class VariableValidatorService
{
    /**
     * Checks the submitted values against the rules of the egg's variables. Below the admin
     * level only variables the user can view and edit are checked and returned.
     *
     * @param  array<string, bool|float|int|string|null>  $fields  environment variable name => submitted value
     * @return Collection<int, ValidatedEggVariable>
     *
     * @throws ValidationException
     */
    public function validate(Egg $egg, array $fields = [], int $userLevel = User::USER_LEVEL_USER): Collection
    {
        $query = $egg->variables();
        if ($userLevel !== User::USER_LEVEL_ADMIN) {
            $query = $query->where('user_editable', true)->where('user_viewable', true);
        }

        $variables = $query->get();
        $data = [];
        $rules = [];
        $customAttributes = [];
        foreach ($variables as $variable) {
            $data['environment'][$variable->env_variable] = Arr::get($fields, $variable->env_variable);
            $rules['environment.'.$variable->env_variable] = $variable->rules;
            $customAttributes['environment.'.$variable->env_variable] = trans('validation.internal.variable_value', ['env' => $variable->name]);
        }

        Validator::make($data, $rules, [], $customAttributes)->validate();

        return $variables->map(fn (EggVariable $item): ValidatedEggVariable => new ValidatedEggVariable(
            $item->id,
            $item->env_variable,
            $fields[$item->env_variable] ?? null,
        ));
    }
}
