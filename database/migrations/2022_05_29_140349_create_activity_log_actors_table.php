<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogActorsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_log_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->references('id')->on('activity_logs')->cascadeOnDelete();
            $table->numericMorphs('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_subjects');
    }
}
