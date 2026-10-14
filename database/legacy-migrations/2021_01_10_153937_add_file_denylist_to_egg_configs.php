<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileDenylistToEggConfigs extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->text('file_denylist')->after('docker_images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table): void {
            $table->dropColumn('file_denylist');
        });
    }
}
