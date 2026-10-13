<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowNullableDescriptions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->text('description')->nullable()->change();
        });

        Schema::table('nests', function (Blueprint $table): void {
            $table->text('description')->nullable()->change();
        });

        Schema::table('nodes', function (Blueprint $table): void {
            $table->text('description')->nullable()->change();
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->text('long')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->text('description')->nullable(false)->change();
        });

        Schema::table('nests', function (Blueprint $table): void {
            $table->text('description')->nullable(false)->change();
        });

        Schema::table('nodes', function (Blueprint $table): void {
            $table->text('description')->nullable(false)->change();
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->text('long')->nullable(false)->change();
        });
    }
}
