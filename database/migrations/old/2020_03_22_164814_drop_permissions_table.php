<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('subuser_id');
            $table->string('permission');

            $table->foreign('subuser_id')->references('id')->on('subusers')->onDelete('cascade');
        });
    }
}
