<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modpack_installations', function (Blueprint $table) {
            $table->string('provider');
            $table->string('modpack_id');
            $table->foreignId('server_id')->unique();
            $table->boolean('finalized')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modpack_installations');
    }
};
