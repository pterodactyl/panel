<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDaemonSecretFromSubusersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $inserts = [];
        $subusers = DB::table('subusers')->get();
        $subusers->each(function ($subuser) use (&$inserts) {
            $inserts[] = [
                'user_id' => $subuser->user_id,
                'server_id' => $subuser->server_id,
                'secret' => 'i_' . str_random(40),
                'expires_at' => Carbon::now()->addHours(24),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
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
    public function down()
    {
        Schema::table('subusers', function (Blueprint $table) {
            $table->char('daemonSecret', 36)->after('server_id')->unique();
        });

        $subusers = DB::table('subusers')->get();
        $subusers->each(function ($subuser) {
            DB::table('daemon_keys')->delete($subuser->id);
        });
    }
}
