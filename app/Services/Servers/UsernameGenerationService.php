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
            $unique = bin2hex(random_bytes(4));
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
