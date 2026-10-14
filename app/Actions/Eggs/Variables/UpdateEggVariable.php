<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Variables;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Eggs\UpdatesEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

final class UpdateEggVariable implements UpdatesEggVariables
{
    public function update(EggVariable $variable, array $input): EggVariable
    {
        $egg = Egg::query()->findOrFail($variable->egg_id);
        $validated = Validator::validate($input, CreateEggVariable::rules($egg, $variable));
        $options = Arr::array($validated, 'options', []);

        // A variable given no description gets a blank one, and the options set whether users
        // can see and edit it; any other option is ignored.
        $variable->update([
            ...Arr::except($validated, 'options'),
            'description' => $validated['description'] ?? '',
            'user_viewable' => in_array('user_viewable', $options, true),
            'user_editable' => in_array('user_editable', $options, true),
        ]);

        return $variable;
    }
}
