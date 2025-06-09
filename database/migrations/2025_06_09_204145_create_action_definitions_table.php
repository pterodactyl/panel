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
        Schema::create('action_definitions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('config_schema')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_definitions');
    }
};
