<?php
/*
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

namespace Pterodactyl\Services\DaemonKeys;

use Carbon\Carbon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyProviderService
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService
     */
    private $keyUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    private $repository;

    /**
     * GetDaemonKeyService constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService        $keyUpdateService
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface $repository
     */
    public function __construct(DaemonKeyUpdateService $keyUpdateService, DaemonKeyRepositoryInterface $repository)
    {
        $this->keyUpdateService = $keyUpdateService;
        $this->repository = $repository;
    }

    /**
     * Get the access key for a user on a specific server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\User   $user
     * @param bool                       $updateIfExpired
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, User $user, $updateIfExpired = true): string
    {
        $userId = $user->root_admin ? $server->owner_id : $user->id;

        $key = $this->repository->findFirstWhere([
            ['user_id', '=', $userId],
            ['server_id', '=', $server->id],
        ]);

        if (! $updateIfExpired || Carbon::now()->diffInSeconds($key->expires_at, false) > 0) {
            return $key->secret;
        }

        return $this->keyUpdateService->handle($key->id);
    }
}
