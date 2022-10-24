<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class RemoveUserInteraction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Remove User Interaction from startup config
        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::table('eggs')->update([
                    'config_startup' => DB::raw('JSON_REMOVE(config_startup, \'$.userInteraction\')'),
                ]);
                break;
            case 'pgsql':
                // TODO: json_remove function
                break;
        }
    }

    public function down()
    {
        // Add blank User Interaction array back to startup config
        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::table('eggs')->update([
                    'config_startup' => DB::raw('JSON_REMOVE(config_startup, \'$.userInteraction\')'),
                ]);
                break;
            case 'pgsql':
                // TODO: json_remove function
                break;
        }
    }
}
