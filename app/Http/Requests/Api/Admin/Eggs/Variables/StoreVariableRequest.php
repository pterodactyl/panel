<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\EggVariableRules;

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
        $rules = EggVariableRules::rules();
        $rules['env_variable'][] = $this->uniqueEnvironmentVariable();

        return array_merge($rules, [
            'options' => ['sometimes', 'required', 'array', 'list'],
            'options.*' => ['string'],
        ]);
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
}
