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
use Webmozart\Assert\Assert;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyProviderService
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService
     */
    protected $keyUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    protected $repository;

    /**
     * GetDaemonKeyService constructor.
     *
     * @param \Carbon\Carbon                                                 $carbon
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyUpdateService        $keyUpdateService
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface $repository
     */
    public function __construct(
        Carbon $carbon,
        DaemonKeyUpdateService $keyUpdateService,
        DaemonKeyRepositoryInterface $repository
    ) {
        $this->carbon = $carbon;
        $this->keyUpdateService = $keyUpdateService;
        $this->repository = $repository;
    }

    /**
     * Get the access key for a user on a specific server.
     *
     * @param int  $server
     * @param int  $user
     * @param bool $updateIfExpired
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($server, $user, $updateIfExpired = true)
    {
        Assert::integerish($server, 'First argument passed to handle must be an integer, received %s.');
        Assert::integerish($user, 'Second argument passed to handle must be an integer, received %s.');

        $key = $this->repository->findFirstWhere([
            ['user_id', '=', $user],
            ['server_id', '=', $server],
        ]);

        if (! $updateIfExpired || max($this->carbon->now()->diffInSeconds($key->expires_at, false), 0) > 0) {
            return $key->secret;
        }

        return $this->keyUpdateService->handle($key->id);
    }
}
