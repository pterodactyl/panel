<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class BuildInitialDatabase extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		DB::unprepared(file_get_contents(database_path('migrations/pgsql/initial.sql')));
	}

	/**
	 * Reverse the migrations.
	 */
	public function down()
	{
		DB::statement('DROP TABLE IF EXISTS
                locations,
				api_logs,
				tasks,
				egg_mount,
				failed_jobs,
				jobs,
				mount_node,
				mount_server,
				mounts,
				nests,
				nodes,
				notifications,
				password_resets,
				sessions,
				settings,
				tasks_log,
				users,
				api_keys,
				eggs,
				nests,
				recovery_tokens,
				database_hosts,
				egg_variables,
				allocations,
				audit_logs,
				backups,
				databases,
				schedules,
				server_transfers,
				server_variables,
				servers,
				subusers
		CASCADE');
	}
}
