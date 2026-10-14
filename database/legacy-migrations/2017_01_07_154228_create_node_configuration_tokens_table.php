<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNodeConfigurationTokensTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('node_configuration_tokens', function (Blueprint $table): void {
            $table->increments('id');
            $table->char('token', 32);
            $table->timestamp('expires_at');
            $table->integer('node')->unsigned();
            $table->foreign('node')->references('id')->on('nodes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_configuration_tokens');
    }
}
