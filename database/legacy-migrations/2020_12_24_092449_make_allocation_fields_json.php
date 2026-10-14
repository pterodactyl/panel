<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeAllocationFieldsJson extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_transfers', function (Blueprint $table): void {
            $table->json('old_additional_allocations')->nullable()->change();
            $table->json('new_additional_allocations')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_transfers', function (Blueprint $table): void {
            $table->string('old_additional_allocations')->nullable()->change();
            $table->string('new_additional_allocations')->nullable()->change();
        });
    }
}
