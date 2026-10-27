<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->char('language', 5)->nullable()->default(null)->change();
        });

        DB::table('users')->where('language', 'en')->update(['language' => null]);
    }

    public function down(): void
    {
        DB::table('users')->whereNull('language')->update(['language' => 'en']);

        Schema::table('users', function (Blueprint $table): void {
            $table->char('language', 5)->default('en')->change();
        });
    }
};
