<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignServiceOptions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE service_options MODIFY parent_service INT(10) UNSIGNED NOT NULL');

        Schema::table('service_options', function (Blueprint $table) {
            $table->foreign('parent_service')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign('service_options_parent_service_foreign');
            $table->dropIndex('service_options_parent_service_foreign');
        });

        DB::statement('ALTER TABLE service_options MODIFY parent_service MEDIUMINT(8) UNSIGNED NOT NULL');
    }
}
