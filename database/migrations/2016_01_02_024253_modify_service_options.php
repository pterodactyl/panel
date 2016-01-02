<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyServiceOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropColumn('config_file');
            $table->dropColumn('config_blob');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->string('config_file')->after('description');
            $table->binary('config_blob')->after('config_file');
        });
    }
}
