<?php

namespace Pterodactyl\Services;

use DB;
use Uuid;

class UuidService
{

    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * Generate a unique UUID validating against specified table and column.
     * Defaults to `users.uuid`
     *
     * @param  string $table
     * @param  string $field
     * @param  integer $type The type of UUID to generate.
     * @return string
     */
    public function generate($table = 'users', $field = 'uuid', $type = 4)
    {

        $return = false;
        do {

            $uuid = Uuid::generate($type);
            if (!DB::table($table)->where($field, $uuid)->exists()) {
                $return = $uuid;
            }

        } while (!$return);

        return $return;

    }

    /**
     * Generates a ShortUUID code which is 8 characters long and is used for identifying servers in the system.
     *
     * @param string $table
     * @param string $field
     * @return string
     */
    public function generateShort($table = 'servers', $field = 'uuidShort', $attachedUuid = null)
    {

        $return = false;
        do {

            $short = (is_null($attachedUuid)) ? substr(Uuid::generate(4), 0, 8) : substr($attachedUuid, 0, 8);
            $attachedUuid = null;

            if (!DB::table($table)->where($field, $short)->exists()) {
                $return = $short;
            }

        } while (!$return);

        return $return;

    }

}
