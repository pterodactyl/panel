<?php

namespace Pterodactyl\Services;

use DB;
use Uuid;

class UuidService
{

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var string
     */
    protected $field = 'uuid';

    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * Set the table that we need to be checking in the database.
     *
     * @param  string $table
     * @return void
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the field in the given table that we want to check for a unique UUID.
     *
     * @param  string $field
     * @return void
     */
    public function field($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Generate a unique UUID validating against specified table and column.
     * Defaults to `users.uuid`
     *
     * @param  integer $type The type of UUID to generate.
     * @return string
     */
    public function generate($type = 4)
    {

        $return = false;
        do {

            $uuid = LaravelUUID::generate($type);
            if (!DB::table($this->table)->where($this->field, $uuid)->exists()) {
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
    public function generateShort($table = 'servers', $field = 'uuidShort')
    {

        $return = false;
        do {

            $short = substr(Uuid::generate(4), 0, 8);
            if (!DB::table($table)->where($field, $short)->exists()) {
                $return = $short;
            }

        } while (!$return);

        return $return;

    }

}
