<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update('UPDATE settings SET `key` = \'mail:mailers:smtp:host\' WHERE `key` = \'mail:host\' AND NOT EXISTS (SELECT 1 FROM settings WHERE `key` = \'mail:mailers:smtp:host\')');
        DB::update('UPDATE settings SET `key` = \'mail:mailers:smtp:port\' WHERE `key` = \'mail:port\' AND NOT EXISTS (SELECT 1 FROM settings WHERE `key` = \'mail:mailers:smtp:port\')');
        DB::update('UPDATE settings SET `key` = \'mail:mailers:smtp:encryption\' WHERE `key` = \'mail:encryption\' AND NOT EXISTS (SELECT 1 FROM settings WHERE `key` = \'mail:mailers:smtp:encryption\')');
        DB::update('UPDATE settings SET `key` = \'mail:mailers:smtp:username\' WHERE `key` = \'mail:username\' AND NOT EXISTS (SELECT 1 FROM settings WHERE `key` = \'mail:mailers:smtp:username\')');
        DB::update('UPDATE settings SET `key` = \'mail:mailers:smtp:password\' WHERE `key` = \'mail:password\' AND NOT EXISTS (SELECT 1 FROM settings WHERE `key` = \'mail:mailers:smtp:password\')');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            DB::delete('DELETE FROM settings WHERE `key` IN (\'mail:host\', \'mail:port\', \'mail:encryption\', \'mail:username\', \'mail:password\')');

            DB::update('UPDATE settings SET `key` = \'mail:host\' WHERE `key` = \'mail:mailers:smtp:host\'');
            DB::update('UPDATE settings SET `key` = \'mail:port\' WHERE `key` = \'mail:mailers:smtp:port\'');
            DB::update('UPDATE settings SET `key` = \'mail:encryption\' WHERE `key` = \'mail:mailers:smtp:encryption\'');
            DB::update('UPDATE settings SET `key` = \'mail:username\' WHERE `key` = \'mail:mailers:smtp:username\'');
            DB::update('UPDATE settings SET `key` = \'mail:password\' WHERE `key` = \'mail:mailers:smtp:password\'');
        });
    }
};
