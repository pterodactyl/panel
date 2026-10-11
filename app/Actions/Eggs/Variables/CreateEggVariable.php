<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Eggs\CreatesEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Rules\UnreservedEnvironmentVariable;
use Pterodactyl\Rules\ValidRuleSet;

final class CreateEggVariable implements CreatesEggVariables
{
    /**
     * An egg variable's fields, whether it is written through the admin API or arrives in an
     * imported egg, and the options that set whether users can see and edit it. Pass the egg
     * to hold the environment variable name unique among its variables, and the variable
     * being updated so its own name is not taken for a duplicate.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Egg $egg = null, ?EggVariable $ignore = null): array
    {
        $environmentVariable = ['required', 'regex:/^[\w]{1,191}$/', new UnreservedEnvironmentVariable];
        if ($egg instanceof Egg) {
            $environmentVariable[] = Rule::unique(EggVariable::class, 'env_variable')->where('egg_id', $egg->id)->ignore($ignore);
        }

        return [
            'name' => ['required', 'string', 'min:1', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string'],
            'env_variable' => $environmentVariable,
            'rules' => ['bail', 'required', 'string', new ValidRuleSet],
            'default_value' => ['present', 'string'],
            'options' => ['sometimes', 'required', 'array', 'list'],
            'options.*' => ['string'],
        ];
    }

    /**
     * Validates variable input for the egg and returns the model attributes it describes.
     * The options become the user_viewable and user_editable flags, each set only when it is
     * listed; any other option is ignored. A variable given no description gets a blank one.
     *
     * @param  array<string, mixed>  $input
     * @return array{name: string, description: string, env_variable: string, default_value: string, user_viewable: bool, user_editable: bool, rules: string}
     */
    public static function attributes(array $input, Egg $egg, ?EggVariable $ignore = null): array
    {
        $input = Validator::make($input, self::rules($egg, $ignore))->safe();

        $options = array_map(fn (int|string $key): string => $input->string('options.'.$key)->toString(), array_keys($input->array('options')));

        return [
            'name' => $input->string('name')->toString(),
            'description' => $input->string('description')->toString(),
            'env_variable' => $input->string('env_variable')->toString(),
            'default_value' => $input->string('default_value')->toString(),
            'user_viewable' => in_array('user_viewable', $options, true),
            'user_editable' => in_array('user_editable', $options, true),
            'rules' => $input->string('rules')->toString(),
        ];
    }

    /**
     * Create a new variable for a given Egg.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(Egg $egg, array $input): EggVariable
    {
        $variable = $egg->variables()->create(self::attributes($input, $egg));

        return $variable->refresh();
    }
}
