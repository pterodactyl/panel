<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogActorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_log_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->references('id')->on('activity_logs')->cascadeOnDelete();
            $table->numericMorphs('subject');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_log_subjects');
    }
}
