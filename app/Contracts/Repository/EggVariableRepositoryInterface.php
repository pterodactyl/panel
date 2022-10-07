<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface EggVariableRepositoryInterface extends RepositoryInterface
{
    /**
     * Return editable variables for a given egg. Editable variables must be set to
     * user viewable in order to be picked up by this function.
     */
    public function getEditableVariables(int $egg): Collection;
}
