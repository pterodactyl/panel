<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Events\Auth;

use Illuminate\Queue\SerializesModels;

class FailedCaptcha
{
    use SerializesModels;

    /**
     * The IP that the request originated from.
     *
     * @var string
     */
    public $ip;

    /**
     * The domain that was used to try to verify the request with recaptcha api.
     *
     * @var string
     */
    public $domain;

    /**
     * Create a new event instance.
     *
     * @param string $ip
     * @param string $domain
     */
    public function __construct($ip, $domain)
    {
        $this->ip = $ip;
        $this->domain = $domain;
    }
}
