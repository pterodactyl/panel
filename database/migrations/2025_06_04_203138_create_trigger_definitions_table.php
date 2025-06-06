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
        Schema::create('trigger_definitions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config_schema')->nullable();
        });

        Schema::table('hook_triggers', function (Blueprint $table) {
            $table->foreignId('trigger_definition_id')->constrained('trigger_definitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hook_triggers', function (Blueprint $table) {
            $table->dropForeign(['trigger_definition_id']);
            $table->dropColumn('trigger_definition_id');
        });
        Schema::dropIfExists('trigger_definitions');
    }
};
