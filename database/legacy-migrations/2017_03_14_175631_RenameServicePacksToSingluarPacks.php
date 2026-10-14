<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameServicePacksToSingluarPacks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_packs', function (Blueprint $table): void {
            $table->dropForeign(['option_id']);
        });

        Schema::rename('service_packs', 'packs');

        Schema::table('packs', function (Blueprint $table): void {
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packs', function (Blueprint $table): void {
            $table->dropForeign(['option_id']);
        });

        Schema::rename('packs', 'service_packs');

        Schema::table('service_packs', function (Blueprint $table): void {
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }
}
