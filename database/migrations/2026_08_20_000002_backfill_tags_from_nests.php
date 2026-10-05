<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Services\Tags\TagBackfiller;

return new class extends Migration
{
    /**
     * One-time cutover of the existing nest grouping into the tags system.
     * Idempotent and insert-only, but guarded so a fresh install with no nests —
     * and any future removal of the table — is a no-op.
     */
    public function up(): void
    {
        if (! Schema::hasTable('nests')) {
            return;
        }

        (new TagBackfiller(DB::connection()))->backfill();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data backfill: tags created here are left in place on rollback.
    }
};
