<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDaemonSecretFromSubusersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $inserts = [];
        $subusers = DB::table('subusers')->get();
        $subusers->each(function ($subuser) use (&$inserts) {
            $inserts[] = [
                'user_id' => $subuser->user_id,
                'server_id' => $subuser->server_id,
                // 'i_' matches DaemonKeyRepositoryInterface::INTERNAL_KEY_IDENTIFIER, removed in 703f55271
                // along with the rest of the daemon-key system; the literal is kept here since this
                // migration still needs to run, unchanged, for anyone migrating from a pre-2020 install.
                'secret' => 'i_' . str_random(40),
                'expires_at' => Carbon::now()->addMinutes(config('pterodactyl.api.key_expire_time', 720))->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        });

        DB::transaction(function () use ($inserts) {
            DB::table('daemon_keys')->insert($inserts);
        });

        Schema::table('subusers', function (Blueprint $table) {
            $table->dropUnique(['daemonSecret']);
            $table->dropColumn('daemonSecret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subusers', function (Blueprint $table) {
            $table->char('daemonSecret', 36)->after('server_id');
        });

        $subusers = DB::table('subusers')->get();
        $subusers->each(function ($subuser) {
            DB::table('daemon_keys')->where('user_id', $subuser->user_id)->where('server_id', $subuser->server_id)->delete();
        });

        Schema::table('subusers', function (Blueprint $table) {
            $table->unique('daemonSecret');
        });
    }
}
