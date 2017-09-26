<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
