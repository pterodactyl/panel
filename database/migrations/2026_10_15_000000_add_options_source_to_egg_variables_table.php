<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egg_variables', function (Blueprint $table): void {
            $table->json('options_source')->nullable()->after('rules');
        });
    }

    public function down(): void
    {
        Schema::table('egg_variables', function (Blueprint $table): void {
            $table->dropColumn('options_source');
        });
    }
};
