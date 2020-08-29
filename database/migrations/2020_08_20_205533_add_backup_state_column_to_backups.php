<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackupStateColumnToBackups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->boolean('is_successful')->after('uuid')->default(true);
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
            $table->dropColumn('is_successful');
        });
    }
}
