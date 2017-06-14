<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Observers;

use Pterodactyl\Events;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Components\UuidService;

class UserObserver
{
    protected $uuid;

    public function __construct(UuidService $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Listen to the User creating event.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return void
     */
    public function creating(User $user)
    {
        $user->uuid = $this->uuid->generate('users', 'uuid');

        event(new Events\User\Creating($user));
    }

    /**
     * Listen to the User created event.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        event(new Events\User\Created($user));
    }

    /**
     * Listen to the User deleting event.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return void
     */
    public function deleting(User $user)
    {
        event(new Events\User\Deleting($user));
    }

    /**
     * Listen to the User deleted event.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        event(new Events\User\Deleted($user));
    }
}
