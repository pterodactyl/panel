<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Permission;

class SetupPermissionsPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedInteger('subuser_id')->after('id');
        });

        DB::transaction(function () {
            foreach(Subuser::all() as &$subuser) {
                Permission::where('user_id', $subuser->user_id)->where('server_id', $subuser->server_id)->update([
                    'subuser_id' => $subuser->id,
                ]);
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign('permissions_server_id_foreign');
            $table->dropIndex('permissions_server_id_foreign');
            $table->dropForeign('permissions_user_id_foreign');
            $table->dropIndex('permissions_user_id_foreign');

            $table->dropColumn('server_id');
            $table->dropColumn('user_id');
            $table->foreign('subuser_id')->references('id')->on('subusers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedInteger('server_id')->after('subuser_id');
            $table->unsignedInteger('user_id')->after('server_id');
        });

        DB::transaction(function () {
            foreach(Subuser::all() as &$subuser) {
                Permission::where('subuser_id', $subuser->id)->update([
                    'user_id' => $subuser->user_id,
                    'server_id' => $subuser->server_id,
                ]);
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign('permissions_subuser_id_foreign');
            $table->dropIndex('permissions_subuser_id_foreign');
            $table->dropColumn('subuser_id');

            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}
