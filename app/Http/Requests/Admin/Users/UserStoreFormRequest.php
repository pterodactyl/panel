<?php

namespace Pterodactyl\Http\Requests\Admin\Users;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class UserStoreFormRequest extends AdminFormRequest
{
    /**
     * Rules to apply to requests for updating a users
     * storefront balances via the admin panel.
     */
    public function rules()
    {
        return Collection::make(
            User::getRulesForUpdate($this->route()->parameter('user'))
        )->only([
            'store_balance',
            'store_slot',
            'store_cpu',
            'store_memory',
            'store_disk',
        ])->toArray();
    }
}