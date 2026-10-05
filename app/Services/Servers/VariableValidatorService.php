<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Data\ValidatedEggVariable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\User;
use Pterodactyl\Traits\Services\HasUserLevels;

class VariableValidatorService
{
    use HasUserLevels;

    /**
     * VariableValidatorService constructor.
     */
    public function __construct(private ValidationFactory $validator) {}

    /**
     * Validate all of the passed data against the given service option variables.
     *
     * @param  array<string, ApiScalar>  $fields  environment variable name => submitted value
     * @return Collection<int, ValidatedEggVariable>
     *
     * @throws ValidationException
     */
    public function handle(Egg $egg, array $fields = []): Collection
    {
        $query = $egg->variables();
        if (! $this->isUserLevel(User::USER_LEVEL_ADMIN)) {
            // Don't attempt to validate variables if they aren't user editable,
            // and we're not running this at an admin level.
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

        $validator = $this->validator->make($data, $rules, [], $customAttributes);
        throw_if($validator->fails(), ValidationException::class, $validator);

        return $variables->map(fn (EggVariable $item): ValidatedEggVariable => new ValidatedEggVariable(
            $item->id,
            $item->env_variable,
            $fields[$item->env_variable] ?? null,
        ));
    }
}
