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
        Schema::table('nodes', function (Blueprint $table) {
            $table->renameColumn('`daemonListen`', 'listen_port_http');
            $table->renameColumn('`daemonSFTP`', 'listen_port_sftp');
            $table->renameColumn('`daemonBase`', 'daemon_base');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('listen_port_http')->unsigned()->default(8080)->after('fqdn')->change();
            $table->integer('listen_port_sftp')->unsigned()->default(2022)->after('listen_port_http')->change();

            $table->integer('public_port_http')->unsigned()->default(8080)->after('listen_port_sftp');
            $table->integer('public_port_sftp')->unsigned()->default(2022)->after('public_port_http');
        });

        DB::transaction(function () {
            foreach (DB::select('SELECT id, listen_port_http, listen_port_sftp FROM nodes') as $datum) {
                DB::update('UPDATE nodes SET public_port_http = ?, public_port_sftp = ? WHERE id = ?', [
                    $datum->listen_port_http,
                    $datum->listen_port_sftp,
                    $datum->id,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->renameColumn('listen_port_http', '`daemonListen`');
            $table->renameColumn('listen_port_sftp', '`daemonSFTP`');
            $table->renameColumn('daemon_base', '`daemonBase`');

            $table->dropColumn('public_port_http');
            $table->dropColumn('public_port_sftp');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->smallInteger('daemonListen')->unsigned()->default(8080)->after('daemon_token')->change();
            $table->smallInteger('daemonSFTP')->unsigned()->default(2022)->after('daemonListen')->change();
        });
    }
};
