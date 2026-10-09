<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;

class ServerDetailsFormRequest extends AdminFormRequest
{
    /**
     * Rules to apply to requests for updating a server's details
     * in the Admin CP.
     */
    public function rules(): array
    {
        $rules = Collection::make(
            Server::getRulesForUpdate($this->route()->parameter('server'))
        )->only([
            'external_id',
            'owner_id',
            'name',
            'description',
        ])->toArray();

        $rules['description'][] = 'nullable';

        return $rules;
    }
}
