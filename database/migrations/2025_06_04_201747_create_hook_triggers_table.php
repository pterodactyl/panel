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
        Schema::create('hook_triggers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('hook_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->json('config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hook_triggers');
    }
};
