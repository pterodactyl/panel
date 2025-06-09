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

        Schema::table('hook_actions', function (Blueprint $table) {
            $table->foreignId('action_definition_id')->constrained('action_definitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hook_actions', function (Blueprint $table) {
            $table->dropForeign(['action_definition_id']);
            $table->dropColumn('action_definition_id');
        });
        Schema::dropIfExists('action_definitions');
    }
};
