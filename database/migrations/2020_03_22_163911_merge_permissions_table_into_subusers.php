<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\Permission as P;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MergePermissionsTableIntoSubusers extends Migration
{
    /**
     * A list of all pre-1.0 permissions available to a user and their associated
     * casting for the new permissions system.
     *
     * @var array
     */
    protected static $permissionsMap = [
        'power-start' => P::ACTION_CONTROL_START,
        'power-stop' => P::ACTION_CONTROL_STOP,
        'power-restart' => P::ACTION_CONTROL_RESTART,
        'power-kill' => P::ACTION_CONTROL_STOP,
        'send-command' => P::ACTION_CONTROL_CONSOLE,
        'list-subusers' => P::ACTION_USER_READ,
        'view-subuser' => P::ACTION_USER_READ,
        'edit-subuser' => P::ACTION_USER_UPDATE,
        'create-subuser' => P::ACTION_USER_CREATE,
        'delete-subuser' => P::ACTION_USER_DELETE,
        'view-allocations' => P::ACTION_ALLOCATION_READ,
        'edit-allocation' => P::ACTION_ALLOCATION_UPDATE,
        'view-startup' => P::ACTION_STARTUP_READ,
        'edit-startup' => P::ACTION_STARTUP_UPDATE,
        'view-databases' => P::ACTION_DATABASE_READ,
        // Better to just break this flow a bit than accidentally grant a dangerous permission.
        'reset-db-password' => P::ACTION_DATABASE_UPDATE,
        'delete-database' => P::ACTION_DATABASE_DELETE,
        'create-database' => P::ACTION_DATABASE_CREATE,
        'access-sftp' => P::ACTION_FILE_SFTP,
        'list-files' => P::ACTION_FILE_READ,
        'edit-files' => P::ACTION_FILE_READ_CONTENT,
        'save-files' => P::ACTION_FILE_UPDATE,
        'create-files' => P::ACTION_FILE_CREATE,
        'delete-files' => P::ACTION_FILE_DELETE,
        'compress-files' => P::ACTION_FILE_ARCHIVE,
        'list-schedules' => P::ACTION_SCHEDULE_READ,
        'view-schedule' => P::ACTION_SCHEDULE_READ,
        'edit-schedule' => P::ACTION_SCHEDULE_UPDATE,
        'create-schedule' => P::ACTION_SCHEDULE_CREATE,
        'delete-schedule' => P::ACTION_SCHEDULE_DELETE,
        // Skipping these permissions as they are granted if you have more specific read/write permissions.
        'move-files' => null,
        'copy-files' => null,
        'decompress-files' => null,
        'upload-files' => null,
        'download-files' => null,
        // These permissions do not exist in 1.0
        'toggle-schedule' => null,
        'queue-schedule' => null,
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subusers', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('server_id');
        });

        $cursor = DB::table('permissions')
            ->select(['subuser_id'])
            ->selectRaw('GROUP_CONCAT(permission) as permissions')
            ->from('permissions')
            ->groupBy(['subuser_id'])
            ->cursor();

        DB::transaction(function () use (&$cursor) {
            $cursor->each(function ($datum) {
                $updated = Collection::make(explode(',', $datum->permissions))
                    ->map(function ($value) {
                        return self::$permissionsMap[$value] ?? null;
                    })->filter(function ($value) {
                        return !is_null($value) && $value !== Permission::ACTION_WEBSOCKET_CONNECT;
                    })
                    // All subusers get this permission, so make sure it gets pushed into the array.
                    ->merge([Permission::ACTION_WEBSOCKET_CONNECT])
                    ->unique()
                    ->values()
                    ->toJson();

                DB::update('UPDATE subusers SET permissions = ? WHERE id = ?', [$updated, $datum->subuser_id]);
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $flipped = array_flip(array_filter(self::$permissionsMap));

        foreach (DB::select('SELECT id, permissions FROM subusers') as $datum) {
            $values = [];
            foreach (json_decode($datum->permissions, true) as $permission) {
                $v = $flipped[$permission] ?? null;
                if (!empty($v)) {
                    $values[] = $datum->id;
                    $values[] = $v;
                }
            }

            if (!empty($values)) {
                $string = 'VALUES ' . implode(', ', array_fill(0, count($values) / 2, '(?, ?)'));

                DB::insert('INSERT INTO permissions(`subuser_id`, `permission`) ' . $string, $values);
            }
        }

        Schema::table('subusers', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
}
