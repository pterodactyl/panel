<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyChecksumsColumnForBackups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->renameColumn('sha256_hash', 'checksum');
        });

        Schema::table('backups', function (Blueprint $table) {
            DB::update('UPDATE backups SET checksum = CONCAT(\'sha256:\', checksum)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->renameColumn('checksum', 'sha256_hash');
        });

        Schema::table('backups', function (Blueprint $table) {
            DB::update('UPDATE backups SET sha256_hash = SUBSTRING(sha256_hash, 8)');
        });
    }
}
