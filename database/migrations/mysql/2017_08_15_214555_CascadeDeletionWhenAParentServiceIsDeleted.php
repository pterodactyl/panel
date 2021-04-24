<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CascadeDeletionWhenAParentServiceIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['service_id']);

            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['service_id']);

            $table->foreign('service_id')->references('id')->on('services');
        });
    }
}
