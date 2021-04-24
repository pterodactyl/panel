<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePackWhenParentServiceOptionIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);

            $table->foreign('option_id')->references('id')->on('service_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);

            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }
}
