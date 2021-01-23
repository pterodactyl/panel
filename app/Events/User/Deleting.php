<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Events\User;

use Pterodactyl\Models\User;
use Illuminate\Queue\SerializesModels;

class Deleting
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     *
     * @var \Pterodactyl\Models\User
     */
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
