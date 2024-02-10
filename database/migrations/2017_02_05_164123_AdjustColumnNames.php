<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdjustColumnNames extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['parent_service']);

            $table->renameColumn('parent_service', 'service_id');
            $table->foreign('service_id')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropIndex(['service_id']);

            $table->renameColumn('service_id', 'parent_service');
            $table->foreign('parent_service')->references('id')->on('services');
        });
    }
}
