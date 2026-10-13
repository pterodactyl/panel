<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteNodeConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('node_configuration_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('node_configuration_tokens', function (Blueprint $table): void {
            $table->increments('id');
            $table->char('token', 32);
            $table->unsignedInteger('node_id');
            $table->timestamps();
        });

        Schema::table('node_configuration_tokens', function (Blueprint $table): void {
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }
}
