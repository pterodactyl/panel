<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModifyChecksumsColumnForBackups extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table): void {
            $table->renameColumn('sha256_hash', 'checksum');
        });

        Schema::table('backups', function (Blueprint $table): void {
            DB::update("UPDATE backups SET checksum = CONCAT('sha256:', checksum)");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table): void {
            $table->renameColumn('checksum', 'sha256_hash');
        });

        Schema::table('backups', function (Blueprint $table): void {
            DB::update('UPDATE backups SET sha256_hash = SUBSTRING(sha256_hash, 8)');
        });
    }
}
