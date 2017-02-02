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

namespace Pterodactyl\Services;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Notifications\Daemon;

class NotificationService
{
    protected $server;

    protected $user;

    /**
     * Daemon will pass an event name, this matches that event name with the notification to send.
     * @var array
     */
    protected $types = [
        // 'crashed' => 'CrashNotification',
        // 'started' => 'StartNotification',
        // 'stopped' => 'StopNotification',
        // 'rebuild' => 'RebuildNotification'
    ];

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->user = User::findOrFail($server->owner_id);
    }

    public function pass(array $notification)
    {
        if (! $notification->type) {
            return;
        }

        if (class_exists($this->types[$notification->type]::class)) {
            $user->notify(new $this->types[$notification->type]($notification->payload));
        }
    }
}
