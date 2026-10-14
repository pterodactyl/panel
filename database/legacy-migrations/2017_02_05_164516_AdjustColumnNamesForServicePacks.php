<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustColumnNamesForServicePacks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_packs', function (Blueprint $table): void {
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
        Schema::table('service_packs', function (Blueprint $table): void {
            $table->dropForeign(['option_id']);
            $table->dropIndex(['option_id']);

            $table->renameColumn('option_id', 'option');
            $table->foreign('option')->references('id')->on('service_options');
        });
    }
}
