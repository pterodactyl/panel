<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCopyScriptFromColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->unsignedInteger('copy_script_from')->nullable()->after('script_container');

            $table->foreign('copy_script_from')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['copy_script_from']);
            $table->dropColumn('copy_script_from');
        });
    }
}
