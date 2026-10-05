<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignServiceVariables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_variables', function (Blueprint $table): void {
            $table->integer('option_id', false, true)->change();
            $table->foreign('option_id')->references('id')->on('service_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_variables', function (Blueprint $table): void {
            $table->dropForeign(['option_id']);
            $table->dropIndex(['option_id']);

            $table->mediumInteger('option_id', false, true)->change();
        });
    }
}
