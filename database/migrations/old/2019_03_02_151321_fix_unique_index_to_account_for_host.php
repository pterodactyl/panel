<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixUniqueIndexToAccountForHost extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropUnique(['database']);
            $table->dropUnique(['username']);

            $table->unique(['database_host_id', 'database']);
            $table->unique(['database_host_id', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table): void {
            $table->dropForeign(['database_host_id']);

            $table->dropUnique(['database_host_id', 'database']);
            $table->dropUnique(['database_host_id', 'username']);

            $table->unique(['database']);
            $table->unique(['username']);
        });
    }
}
