<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdjustColumnNamesForServicePacks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_packs', function (Blueprint $table) {
            $table->dropForeign(['option']);

            $table->renameColumn('option', 'option_id');
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->dropIndex(['option_id']);

            $table->renameColumn('option_id', 'option');
            $table->foreign('option')->references('id')->on('service_options');
        });
    }
}
