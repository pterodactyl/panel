<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subusers', function (Blueprint $table): void {
            $table->unique(['server_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('subusers', function (Blueprint $table): void {
            $table->dropUnique(['server_id', 'user_id']);
        });
    }
};
