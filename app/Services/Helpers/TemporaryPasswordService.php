<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
