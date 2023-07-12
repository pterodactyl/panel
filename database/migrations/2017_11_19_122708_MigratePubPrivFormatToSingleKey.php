<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Contracts\Encryption\DecryptException;

class MigratePubPrivFormatToSingleKey extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('api_keys')->get()->each(function ($item) {
                try {
                    $decrypted = Crypt::decrypt($item->secret);
                } catch (DecryptException) {
                    $decrypted = str_random(32);
                } finally {
                    DB::table('api_keys')->where('id', $item->id)->update([
                        'secret' => $decrypted,
                    ]);
                }
            });
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('public');
            $table->renameColumn('secret', 'token');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->char('token', 32)->change();
            $table->unique('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->renameColumn('token', 'secret');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropUnique('token');
            $table->text('token')->change();
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->renameColumn('token', 'secret');

            $table->text('secret')->nullable()->change();
            $table->char('public', 16)->after('user_id');
        });

        DB::transaction(function () {
            DB::table('api_keys')->get()->each(function ($item) {
                DB::table('api_keys')->where('id', $item->id)->update([
                    'public' => Str::random(16),
                    'secret' => Crypt::encrypt($item->secret),
                ]);
            });
        });
    }
}
