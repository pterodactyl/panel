<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdjustColumnNamesForServicePacks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_packs', function (Blueprint $table) {
            $table->dropForeign('service_packs_option_foreign');
            $table->dropIndex('service_packs_option_foreign');

            $table->renameColumn('option', 'option_id');
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_packs', function (Blueprint $table) {
            $table->dropForeign('service_packs_option_id_foreign');
            $table->dropIndex('service_packs_option_id_foreign');

            $table->renameColumn('option_id', 'option');
            $table->foreign('option')->references('id')->on('service_options');
        });
    }
}
