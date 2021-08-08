<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGenericServerStatusColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('status')->nullable()->after('description');
        });

        DB::transaction(function () {
            DB::update('UPDATE servers SET `status` = \'suspended\' WHERE `suspended` = 1');
            DB::update('UPDATE servers SET `status` = \'installing\' WHERE `installed` = 0');
            DB::update('UPDATE servers SET `status` = \'install_failed\' WHERE `installed` = 2');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('suspended');
            $table->dropColumn('installed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedTinyInteger('suspended')->default(0);
            $table->unsignedTinyInteger('installed')->default(0);
        });

        DB::transaction(function () {
            DB::update('UPDATE servers SET `suspended` = 1 WHERE `status` = \'suspended\'');
            DB::update('UPDATE servers SET `installed` = 1 WHERE `status` IS NULL');
            DB::update('UPDATE servers SET `installed` = 2 WHERE `status` = \'install_failed\'');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
