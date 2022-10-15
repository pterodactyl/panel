<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class ServerVariableRepository extends EloquentRepository implements ServerVariableRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ServerVariable::class;
    }
}
