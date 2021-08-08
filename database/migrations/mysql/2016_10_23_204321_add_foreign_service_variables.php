<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServiceVariables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE service_variables MODIFY option_id INT(10) UNSIGNED NOT NULL');

        Schema::table('service_variables', function (Blueprint $table) {
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign('service_variables_option_id_foreign');
            $table->dropIndex('service_variables_option_id_foreign');
        });

        DB::statement('ALTER TABLE service_variables MODIFY option_id MEDIUMINT(8) UNSIGNED NOT NULL');
    }
}
