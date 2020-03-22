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

        DB::statement('
            UPDATE subusers as s
                LEFT JOIN (
                    SELECT subuser_id, JSON_ARRAYAGG(permission) as permissions
                    FROM permissions
                    GROUP BY subuser_id
                ) as p ON p.subuser_id = s.id
                SET s.permissions = p.permissions
        ');
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
            foreach(json_decode($datum->permissions, true) as $permission) {
                $values[] = $datum->id;
                $values[] = $permission;
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
