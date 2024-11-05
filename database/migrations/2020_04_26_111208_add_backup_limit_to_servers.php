<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBackupLimitToServers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $db = config('database.default');
        // Same as in the backups migration, we need to handle that plugin messing with the data structure
        // here. If we find a result we'll actually keep the column around since we can maintain that backup
        // limit, but we need to correct the column definition a bit.
        $results = DB::select('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = \'servers\' AND COLUMN_NAME = \'backup_limit\'', [
            config("database.connections.{$db}.database"),
        ]);

        if (count($results) === 1) {
            Schema::table('servers', function (Blueprint $table) {
                $table->unsignedInteger('backup_limit')->default(0)->change();
            });
        } else {
            Schema::table('servers', function (Blueprint $table) {
                $table->unsignedInteger('backup_limit')->default(0)->after('database_limit');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('backup_limit');
        });
    }
}
