<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Rules\ValidRuleSet;
use Pterodactyl\Support\JsonValueGuard;

class StoreVariableRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggVariablesCreate];
    }

    /**
     * Validation rules for creating an egg variable.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string'],
            'env_variable' => [
                'required',
                'regex:/^[\w]{1,191}$/',
                Rule::notIn($this->reservedEnvironmentVariables()),
                $this->uniqueEnvironmentVariable(),
            ],
            'options' => ['sometimes', 'required', 'array', 'list'],
            'options.*' => ['string'],
            'rules' => ['bail', 'required', 'string', new ValidRuleSet],
            'default_value' => ['present', 'string'],
        ];
    }

    /**
     * Reserved names are rejected in any letter case.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $name = $this->string('env_variable')->toString();
                if ($validator->errors()->has('env_variable') || ! in_array(mb_strtoupper($name), $this->reservedEnvironmentVariables(), true)) {
                    return;
                }

                $validator->errors()->add('env_variable', trans('exceptions.service.variables.reserved_name', ['name' => $name]));
            },
        ];
    }

    /**
     * Model attributes for the variable; the options list becomes the two flags.
     *
     * @return EggVariableData
     */
    public function payload(): array
    {
        $options = JsonValueGuard::stringList($this->input('options', []));

        return [
            'name' => $this->string('name')->toString(),
            'description' => JsonValueGuard::nullableString($this->input('description')) ?? '',
            'env_variable' => $this->string('env_variable')->toString(),
            'default_value' => JsonValueGuard::string($this->input('default_value')),
            'user_viewable' => in_array('user_viewable', $options, true),
            'user_editable' => in_array('user_editable', $options, true),
            'rules' => $this->string('rules')->toString(),
        ];
    }

    /**
     * The environment variable name must be unique among the egg's variables.
     */
    protected function uniqueEnvironmentVariable(): Unique
    {
        return Rule::unique(EggVariable::class, 'env_variable')->where('egg_id', $this->parameter('egg', Egg::class)->id);
    }

    /** @return list<string> */
    private function reservedEnvironmentVariables(): array
    {
        return explode(',', EggVariable::RESERVED_ENV_NAMES);
    }
}
