<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eggs no longer belong to a nest — tags carry the grouping. The column is kept
     * as legacy data so the existing nest endpoints and any third-party consumer
     * keep resolving, but new eggs are created without one.
     */
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->unsignedInteger('nest_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw_if(DB::table('eggs')->whereNull('nest_id')->exists(), RuntimeException::class, 'Cannot restore required eggs.nest_id while top-level eggs still exist. Assign every egg to a legacy nest before rolling back.');

        Schema::table('eggs', function (Blueprint $table): void {
            $table->unsignedInteger('nest_id')->nullable(false)->change();
        });
    }
};
