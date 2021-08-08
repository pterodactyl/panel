<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CascadeDeletionWhenServiceOptionIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['option_id']);

            $table->foreign('option_id')->references('id')->on('service_options')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['option_id']);

            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }
}
