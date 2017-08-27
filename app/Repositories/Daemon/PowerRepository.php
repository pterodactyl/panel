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

namespace Pterodactyl\Repositories\Daemon;

use Webmozart\Assert\Assert;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;
use Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException;

class PowerRepository extends BaseRepository implements PowerRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function sendSignal($signal)
    {
        Assert::stringNotEmpty($signal, 'The first argument passed to sendSignal must be a non-empty string, received %s.');

        switch ($signal) {
            case self::SIGNAL_START:
            case self::SIGNAL_STOP:
            case self::SIGNAL_RESTART:
            case self::SIGNAL_KILL:
                return $this->getHttpClient()->request('PUT', '/server/power', [
                    'json' => [
                        'action' => $signal,
                    ],
                ]);
                break;
            default:
                throw new InvalidPowerSignalException('The signal ' . $signal . ' is not defined and could not be processed.');
        }
    }
}
