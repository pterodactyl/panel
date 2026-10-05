<?php

declare(strict_types=1);

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigratePubPrivFormatToSingleKey extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('api_keys')->get()->each(function ($item): void {
                try {
                    $decrypted = Crypt::decrypt($item->secret);
                } catch (DecryptException) {
                    $decrypted = Str::random(32);
                } finally {
                    DB::table('api_keys')->where('id', $item->id)->update([
                        'secret' => $decrypted,
                    ]);
                }
            });
        });

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropColumn('public');
            $table->renameColumn('secret', 'token');
        });

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->char('token', 32)->change();
            $table->unique('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropUnique(['token']);
            $table->renameColumn('token', 'secret');
        });

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropUnique('token');
            $table->text('token')->change();
        });

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->renameColumn('token', 'secret');

            $table->text('secret')->nullable()->change();
            $table->char('public', 16)->after('user_id');
        });

        DB::transaction(function (): void {
            DB::table('api_keys')->get()->each(function ($item): void {
                DB::table('api_keys')->where('id', $item->id)->update([
                    'public' => Str::random(16),
                    'secret' => Crypt::encrypt($item->secret),
                ]);
            });
        });
    }
}
