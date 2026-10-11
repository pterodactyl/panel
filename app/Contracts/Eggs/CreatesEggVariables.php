<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

interface CreatesEggVariables
{
    /**
     * Create a new variable for a given Egg. The input is validated here, so it can come
     * straight from any caller: the environment variable name must be unique among the egg's
     * variables and not one the panel reserves, and the rules must be a valid rule set.
     * Listing user_viewable or user_editable in options lets users see or edit the variable.
     *
     * @param  ActionInput  $input  name, env_variable, rules and default_value, and optionally description and options
     */
    public function create(Egg $egg, array $input): EggVariable;
}
