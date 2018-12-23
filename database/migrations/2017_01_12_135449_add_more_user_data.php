<?php

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreUserData extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_first')->after('email')->nullable();
            $table->string('name_last')->after('name_first')->nullable();
            $table->string('username')->after('uuid');
            $table->boolean('gravatar')->after('totp_secret')->default(true);
        });

        DB::transaction(function () {
            foreach (User::all() as &$user) {
                $user->username = $user->email;
                $user->save();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_first');
            $table->dropColumn('name_last');
            $table->dropColumn('username');
            $table->dropColumn('gravatar');
        });
    }
}
