<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDaemonSecretFromServersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $inserts = [];

        $servers = DB::table('servers')->select('id', 'owner_id')->get();
        $servers->each(function ($server) use (&$inserts) {
            $inserts[] = [
                'user_id' => $server->owner_id,
                'server_id' => $server->id,
                'secret' => 'i_' . str_random(40),
                'expires_at' => Carbon::now()->addHours(24),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        });

        DB::transaction(function () use ($inserts) {
            DB::table('daemon_keys')->insert($inserts);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropUnique(['daemonSecret']);
            $table->dropColumn('daemonSecret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->char('daemonSecret', 36)->after('startup')->unique();
        });

        DB::table('daemon_keys')->truncate();
    }
}
