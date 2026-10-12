<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Illuminate\Support\Arr;
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
     * Create a new variable for a given Egg.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(Egg $egg, array $input): EggVariable
    {
        $validated = Validator::validate($input, self::rules($egg));
        $options = Arr::array($validated, 'options', []);

        // A variable given no description gets a blank one, and the options set whether users
        // can see and edit it; any other option is ignored.
        $variable = $egg->variables()->create([
            ...Arr::except($validated, 'options'),
            'description' => $validated['description'] ?? '',
            'user_viewable' => in_array('user_viewable', $options, true),
            'user_editable' => in_array('user_editable', $options, true),
        ]);

        return $variable->refresh();
    }
}
