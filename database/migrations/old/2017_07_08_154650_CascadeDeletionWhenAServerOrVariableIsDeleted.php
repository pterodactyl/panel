<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CascadeDeletionWhenAServerOrVariableIsDeleted extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_variables', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['variable_id']);

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('variable_id')->references('id')->on('service_variables')->onDelete('cascade');
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

            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('variable_id')->references('id')->on('service_variables');
        });
    }
}
