<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Events\Subuser;

use Pterodactyl\Models\Subuser;
use Illuminate\Queue\SerializesModels;

class Created
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     *
     * @var \Pterodactyl\Models\Subuser
     */
    public $subuser;

    /**
     * Create a new event instance.
     */
    public function __construct(Subuser $subuser)
    {
        $this->subuser = $subuser;
    }
}
