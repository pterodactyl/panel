<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Subuser;

class SetupPermissionsPivotTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->unsignedInteger('subuser_id')->after('id');
        });

        DB::transaction(function (): void {
            foreach (Subuser::all() as &$subuser) {
                Permission::query()->where('user_id', $subuser->user_id)->where('server_id', $subuser->server_id)->update([
                    'subuser_id' => $subuser->id,
                ]);
            }
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['user_id']);

            $table->dropColumn('server_id');
            $table->dropColumn('user_id');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
            $table->foreign('subuser_id')->references('id')->on('subusers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->unsignedInteger('server_id')->after('subuser_id');
            $table->unsignedInteger('user_id')->after('server_id');
            $table->timestamps();
        });

        DB::transaction(function (): void {
            foreach (Subuser::all() as &$subuser) {
                Permission::query()->where('subuser_id', $subuser->id)->update([
                    'user_id' => $subuser->user_id,
                    'server_id' => $subuser->server_id,
                ]);
            }
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropForeign(['subuser_id']);
            $table->dropIndex(['subuser_id']);
            $table->dropColumn('subuser_id');

            $table->foreign('server_id')->references('id')->on('servers');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}
