<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Eggs;

use Pterodactyl\Models\EggVariable;

interface UpdatesEggVariables
{
    /**
     * Update an egg variable. The input is validated here, so it can come straight from any
     * caller. It takes the fields a variable is created with, required ones included; a
     * description left out is blanked, and the variable can only be seen or edited by users
     * when options lists user_viewable or user_editable. The variable keeps its own
     * environment variable name without it counting as taken.
     *
     * @param  ActionInput  $input  the fields a variable is created with
     */
    public function update(EggVariable $variable, array $input): EggVariable;
}
