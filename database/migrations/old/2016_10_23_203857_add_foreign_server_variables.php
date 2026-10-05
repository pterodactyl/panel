<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignServerVariables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_variables', function (Blueprint $table): void {
            $table->integer('server_id', false, true)->nullable()->change();
            $table->integer('variable_id', false, true)->nullable(false)->change();
            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('variable_id')->references('id')->on('service_variables');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_variables', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['variable_id']);
            $table->mediumInteger('server_id', false, true)->nullable()->change();
            $table->mediumInteger('variable_id', false, true)->nullable(false)->change();
        });
    }
}
