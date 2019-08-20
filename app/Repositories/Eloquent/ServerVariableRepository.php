<?php

namespace App\Repositories\Eloquent;

use App\Models\ServerVariable;
use App\Contracts\Repository\ServerVariableRepositoryInterface;

class ServerVariableRepository extends EloquentRepository implements ServerVariableRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return ServerVariable::class;
    }
}
