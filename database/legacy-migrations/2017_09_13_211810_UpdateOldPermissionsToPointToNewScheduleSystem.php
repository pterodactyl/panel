<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateOldPermissionsToPointToNewScheduleSystem extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = DB::table('permissions')->where('permission', 'like', '%-task%')->get();
        foreach ($permissions as $record) {
            $parts = explode('-', $record->permission);
            if (! in_array(Arr::get($parts, 1), ['tasks', 'task']) || count($parts) !== 2) {
                continue;
            }

            $newPermission = $parts[0].'-'.str_replace('task', 'schedule', $parts[1]);

            DB::table('permissions')->where('id', '=', $record->id)->update(['permission' => $newPermission]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = DB::table('permissions')->where('permission', 'like', '%-schedule%')->get();
        foreach ($permissions as $record) {
            $parts = explode('-', $record->permission);
            if (! in_array(Arr::get($parts, 1), ['schedules', 'schedule']) || count($parts) !== 2) {
                continue;
            }

            $newPermission = $parts[0].'-'.str_replace('schedule', 'task', $parts[1]);

            DB::table('permissions')->where('id', '=', $record->id)->update(['permission' => $newPermission]);
        }
    }
}
