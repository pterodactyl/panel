<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::create('downloads', function (Blueprint $table): void {
            $table->increments('id');
            $table->char('token', 36)->unique();
            $table->char('server', 36);
            $table->text('path');
            $table->timestamps();
        });
    }
}
