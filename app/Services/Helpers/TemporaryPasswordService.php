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

namespace Pterodactyl\Services\Helpers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Config\Repository as ConfigRepository;

class TemporaryPasswordService
{
    const HMAC_ALGO = 'sha256';

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * TemporaryPasswordService constructor.
     *
     * @param \Illuminate\Config\Repository        $config
     * @param \Illuminate\Database\DatabaseManager $database
     * @param \Illuminate\Contracts\Hashing\Hasher $hasher
     */
    public function __construct(
        ConfigRepository $config,
        DatabaseManager $database,
        Hasher $hasher
    ) {
        $this->config = $config;
        $this->database = $database;
        $this->hasher = $hasher;
    }

    /**
     * Store a password reset token for a specific email address.
     *
     * @param string $email
     * @return string
     */
    public function generateReset($email)
    {
        $token = hash_hmac(self::HMAC_ALGO, str_random(40), $this->config->get('app.key'));

        $this->database->table('password_resets')->insert([
            'email' => $email,
            'token' => $this->hasher->make($token),
        ]);

        return $token;
    }
}
