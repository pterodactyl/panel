<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

class UsernameGenerationService
{
    /**
     * Generate a unique username to be used for SFTP connections and identification
     * of the server docker container on the host system.
     *
     * @param string $name
     * @param null   $identifier
     * @return string
     */
    public function generate($name, $identifier = null)
    {
        if (is_null($identifier) || ! ctype_alnum($identifier)) {
            $unique = str_random(8);
        } else {
            if (strlen($identifier) < 8) {
                $unique = $identifier . str_random((8 - strlen($identifier)));
            } else {
                $unique = substr($identifier, 0, 8);
            }
        }

        // Filter the Server Name
        $name = trim(preg_replace('/[^A-Za-z0-9]+/', '', $name), '_');
        $name = (strlen($name) < 1) ? str_random(6) : $name;

        return strtolower(substr($name, 0, 6) . '_' . $unique);
    }
}
