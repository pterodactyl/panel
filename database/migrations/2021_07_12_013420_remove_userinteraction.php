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
        DB::table('eggs')->update([
            'config_startup' => DB::raw('JSON_REMOVE(config_startup, \'$.userInteraction\')'),
        ]);
    }

    public function down()
    {
        // Add blank User Interaction array back to startup config
        DB::table('eggs')->update([
            'config_startup' => DB::raw('JSON_SET(config_startup, \'$.userInteraction\', JSON_ARRAY())'),
        ]);
    }
}
