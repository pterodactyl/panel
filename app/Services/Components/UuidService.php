<?php
/**
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

namespace Pterodactyl\Services\Components;

use DB;
use Uuid;

class UuidService
{
    /**
     * Generate a unique UUID validating against specified table and column.
     * Defaults to `users.uuid`.
     *
     * @param string $table
     * @param string $field
     * @param int    $type
     * @return string
     * @deprecated
     */
    public function generate($table = 'users', $field = 'uuid', $type = 4)
    {
        $return = false;
        do {
            $uuid = Uuid::generate($type);
            if (! DB::table($table)->where($field, $uuid)->exists()) {
                $return = $uuid;
            }
        } while (! $return);

        return (string) $return;
    }

    /**
     * Generates a ShortUUID code which is 8 characters long and is used for identifying servers in the system.
     *
     * @param string      $table
     * @param string      $field
     * @param null|string $attachedUuid
     * @return string
     * @deprecated
     */
    public function generateShort($table = 'servers', $field = 'uuidShort', $attachedUuid = null)
    {
        $return = false;
        do {
            $short = (is_null($attachedUuid)) ? substr(Uuid::generate(4), 0, 8) : substr($attachedUuid, 0, 8);
            $attachedUuid = null;

            if (! DB::table($table)->where($field, $short)->exists()) {
                $return = $short;
            }
        } while (! $return);

        return (string) $return;
    }
}
