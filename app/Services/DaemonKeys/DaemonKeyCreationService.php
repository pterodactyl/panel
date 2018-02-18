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
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyCreationService
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface
     */
    protected $repository;

    /**
     * DaemonKeyCreationService constructor.
     *
     * @param \Carbon\Carbon                                                 $carbon
     * @param \Illuminate\Contracts\Config\Repository                        $config
     * @param \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface $repository
     */
    public function __construct(
        Carbon $carbon,
        ConfigRepository $config,
        DaemonKeyRepositoryInterface $repository
    ) {
        $this->carbon = $carbon;
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new daemon key to be used when connecting to a daemon.
     *
     * @param int $server
     * @param int $user
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(int $server, int $user)
    {
        $secret = DaemonKeyRepositoryInterface::INTERNAL_KEY_IDENTIFIER . str_random(40);

        $this->repository->withoutFreshModel()->create([
            'user_id' => $user,
            'server_id' => $server,
            'secret' => $secret,
            'expires_at' => $this->carbon->now()->addMinutes($this->config->get('pterodactyl.api.key_expire_time'))->toDateTimeString(),
        ]);

        return $secret;
    }
}
