<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameServicePacksToSingluarPacks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
        });

        Schema::rename('service_packs', 'packs');

        Schema::table('packs', function (Blueprint $table) {
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
        });

        Schema::rename('packs', 'service_packs');

        Schema::table('service_packs', function (Blueprint $table) {
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }
}
