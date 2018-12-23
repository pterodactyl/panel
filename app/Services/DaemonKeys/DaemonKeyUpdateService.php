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
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyUpdateService
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
     * DaemonKeyUpdateService constructor.
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
     * Update a daemon key to expire the previous one.
     *
     * @param int $key
     * @return string
     *
     * @throws \RuntimeException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($key)
    {
        Assert::integerish($key, 'First argument passed to handle must be an integer, received %s.');

        $secret = DaemonKeyRepositoryInterface::INTERNAL_KEY_IDENTIFIER . str_random(40);
        $this->repository->withoutFreshModel()->update($key, [
            'secret' => $secret,
            'expires_at' => $this->carbon->now()->addMinutes($this->config->get('pterodactyl.api.key_expire_time'))->toDateTimeString(),
        ]);

        return $secret;
    }
}
