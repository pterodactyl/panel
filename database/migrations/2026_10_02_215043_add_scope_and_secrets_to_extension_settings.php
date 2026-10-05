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
        Schema::table('extension_settings', function (Blueprint $table): void {
            $table->string('scope', 64)->default('global');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('server_id')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->dropUnique(['extension', 'key']);
            $table->unique(['extension', 'scope', 'key']);
        });
    }

    public function down(): void
    {
        throw_if(DB::table('extension_settings')->where('scope', '!=', 'global')->orWhere('is_secret', true)->exists(), RuntimeException::class, 'Remove scoped settings and decrypt secrets before rolling back this migration.');

        Schema::table('extension_settings', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['server_id']);
            $table->dropUnique(['extension', 'scope', 'key']);
            $table->dropColumn(['scope', 'user_id', 'server_id', 'is_secret']);
            $table->unique(['extension', 'key']);
        });
    }
};
