<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteDownloadTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('downloads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->increments('id');
            $table->char('token', 36)->unique();
            $table->char('server', 36);
            $table->text('path');
            $table->timestamps();
        });
    }
}
