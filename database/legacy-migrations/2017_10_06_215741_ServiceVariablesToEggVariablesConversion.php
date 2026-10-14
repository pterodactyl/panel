<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ServiceVariablesToEggVariablesConversion extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('service_variables', 'egg_variables');

        Schema::table('server_variables', function (Blueprint $table): void {
            $table->dropForeign(['variable_id']);

            $table->foreign('variable_id')->references('id')->on('egg_variables')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('egg_variables', 'service_variables');

        Schema::table('server_variables', function (Blueprint $table): void {
            $table->dropForeign(['variable_id']);

            $table->foreign('variable_id')->references('id')->on('service_variables')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }
}
