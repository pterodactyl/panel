<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowNegativeValuesForOverallocation extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->integer('disk_overallocate')->default(0)->nullable(false)->change();
            $table->integer('memory_overallocate')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            DB::statement('ALTER TABLE nodes MODIFY disk_overallocate MEDIUMINT UNSIGNED NULL,
                                             MODIFY memory_overallocate MEDIUMINT UNSIGNED NULL');
        });
    }
}
