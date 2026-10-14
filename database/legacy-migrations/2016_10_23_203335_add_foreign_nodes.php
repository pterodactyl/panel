<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignNodes extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->integer('location', false, true)->nullable(false)->change();
            $table->foreign('location')->references('id')->on('locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table): void {
            $table->dropForeign(['location']);
            $table->dropIndex(['location']);

            $table->mediumInteger('location', false, true)->nullable(false)->change();
        });
    }
}
