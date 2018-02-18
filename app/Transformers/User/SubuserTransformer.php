<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Transformers\User;

use Pterodactyl\Models\Subuser;
use League\Fractal\TransformerAbstract;

class SubuserTransformer extends TransformerAbstract
{
    /**
     * Return a generic transformed subuser array.
     *
     * @return array
     */
    public function transform(Subuser $subuser)
    {
        return [
            'id' => $subuser->id,
            'username' => $subuser->user->username,
            'email' => $subuser->user->email,
            '2fa' => (bool) $subuser->user->use_totp,
            'permissions' => $subuser->permissions->pluck('permission'),
        ];
    }
}
