<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdjustColumnNames extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign('service_options_parent_service_foreign');
            $table->dropIndex('service_options_parent_service_foreign');

            $table->renameColumn('parent_service', 'service_id');
            $table->foreign('service_id')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign('service_options_service_id_foreign');
            $table->dropIndex('service_options_service_id_foreign');

            $table->renameColumn('service_id', 'parent_service');
            $table->foreign('parent_service')->references('id')->on('services');
        });
    }
}
