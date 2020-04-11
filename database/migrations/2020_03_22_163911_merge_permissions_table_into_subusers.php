<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MergePermissionsTableIntoSubusers extends Migration
{
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
                DB::update('UPDATE subusers SET permissions = ? WHERE id = ?', [
                    json_encode(explode(',', $datum->permissions)),
                    $datum->subuser_id,
                ]);
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
        foreach (DB::select('SELECT id, permissions FROM subusers') as $datum) {
            $values = [];
            foreach (json_decode($datum->permissions, true) as $permission) {
                $values[] = $datum->id;
                $values[] = $permission;
            }

            if (! empty($values)) {
                $string = 'VALUES ' . implode(', ', array_fill(0, count($values) / 2, '(?, ?)'));

                DB::insert('INSERT INTO permissions(`subuser_id`, `permission`) ' . $string, $values);
            }
        }

        Schema::table('subusers', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
}
