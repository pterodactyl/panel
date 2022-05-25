<?php

namespace Pterodactyl\Http\Requests\Admin\Users;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class UserResourceFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return Collection::make(User::getRulesForUpdate($this->route()->parameter('user')))
            ->only([
                'store_balance',
                'store_slot',
                'store_cpu',
                'store_memory',
                'store_disk',
            ])->toArray();
    }
}