<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSuccessfulNullableInServerTransfers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_transfers', function (Blueprint $table): void {
            $table->boolean('successful')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_transfers', function (Blueprint $table): void {
            $table->boolean('successful')->default(0)->change();
        });
    }
}
