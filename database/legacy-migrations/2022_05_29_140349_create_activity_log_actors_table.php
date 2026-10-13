<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogActorsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_log_subjects', function (Blueprint $table): void {
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
