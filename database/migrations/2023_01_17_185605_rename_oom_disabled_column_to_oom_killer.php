<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->tinyInteger('oom_killer')->unsigned()->default(1)->after('oom_disabled');
        });

        DB::table('servers')->select(['id', 'oom_disabled'])->cursor()->each(function ($server) {
            DB::table('servers')->where('id', $server->id)->update(['oom_killer' => !$server->oom_disabled]);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('oom_disabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->tinyInteger('oom_disabled')->unsigned()->default(0)->after('oom_killer');
        });

        DB::table('servers')->select(['id', 'oom_killer'])->cursor()->each(function ($server) {
            DB::table('servers')->where('id', $server->id)->update(['oom_disabled' => !$server->oom_killer]);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('oom_killer');
        });
    }
};
