<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Add2FaLastAuthorizationTimeColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('totp_secret')->nullable()->change();
            $table->timestampTz('totp_authenticated_at')->after('totp_secret')->nullable();
        });

        DB::transaction(function () {
            DB::table('users')->get()->each(function ($user) {
                if (is_null($user->totp_secret)) {
                    return;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'totp_secret' => Crypt::encrypt($user->totp_secret),
                    'updated_at' => Carbon::now()->toAtomString(),
                ]);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::transaction(function () {
            DB::table('users')->get()->each(function ($user) {
                if (is_null($user->totp_secret)) {
                    return;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'totp_secret' => Crypt::decrypt($user->totp_secret),
                    'updated_at' => Carbon::now()->toAtomString(),
                ]);
            });
        });

        DB::statement('ALTER TABLE users MODIFY totp_secret CHAR(16) DEFAULT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('totp_authenticated_at');
        });
    }
}
