<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('extensions') || ! Schema::hasColumn('extensions', 'quarantined_at')) {
            return;
        }

        Schema::table('extensions', function (Blueprint $table): void {
            $table->dropColumn('quarantined_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('extensions') || Schema::hasColumn('extensions', 'quarantined_at')) {
            return;
        }

        Schema::table('extensions', function (Blueprint $table): void {
            $table->timestamp('quarantined_at')->nullable()->after('error');
        });
    }
};
