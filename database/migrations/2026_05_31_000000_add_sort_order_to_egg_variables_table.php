<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a nullable sort_order column so an administrator can persist the display
     * order of an egg's variables. Existing rows keep a null order and fall back to
     * insertion (id) order.
     */
    public function up(): void
    {
        Schema::table('egg_variables', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->nullable()->after('rules');
        });
    }

    public function down(): void
    {
        Schema::table('egg_variables', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
