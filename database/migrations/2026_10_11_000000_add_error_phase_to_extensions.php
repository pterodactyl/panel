<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extensions', function (Blueprint $table): void {
            $table->string('error_phase', 20)->nullable()->after('error');
            $table->timestamp('errored_at')->nullable()->after('error_phase');
        });
    }

    public function down(): void
    {
        Schema::table('extensions', function (Blueprint $table): void {
            $table->dropColumn(['error_phase', 'errored_at']);
        });
    }
};
