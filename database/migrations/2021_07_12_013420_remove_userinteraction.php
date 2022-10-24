<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class RemoveUserInteraction extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove User Interaction from startup config
        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::table('eggs')->update([
                    'config_startup' => DB::raw('JSON_REMOVE(config_startup, \'$.userInteraction\')'),
                ]);
                break;
            case 'pgsql':
                DB::table('eggs')->update([
                    'config_startup' => DB::raw('config_startup::jsonb - \'userInteraction\''),
                ]);
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add blank User Interaction array back to startup config
        switch (DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                DB::table('eggs')->update([
                    'config_startup' => DB::raw('JSON_SET(config_startup, \'$.userInteraction\', JSON_ARRAY())'),
                ]);
                break;
            case 'pgsql':
                DB::table('eggs')->update([
                    'config_startup' => DB::raw('jsonb_set(config_startup::jsonb, \'$.userInteraction\', jsonb_build_array())'),
                ]);
                break;
        }
    }
}
