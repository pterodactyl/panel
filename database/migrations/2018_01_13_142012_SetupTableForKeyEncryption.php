<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetupTableForKeyEncryption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function up()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->char('identifier', 16)->unique()->after('user_id');
            $table->dropUnique(['token']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->text('token')->change();
        });

        DB::transaction(function () {
            foreach (DB::table('api_keys')->cursor() as $key) {
                DB::table('api_keys')->where('id', $key->id)->update([
                    'identifier' => str_random(16),
                    'token' => Crypt::encrypt($key->token),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function down()
    {
        /* @var \Pterodactyl\Models\APIKey $key */
        DB::transaction(function () {
            foreach (DB::table('api_keys')->cursor() as $key) {
                DB::table('api_keys')->where('id', $key->id)->update([
                    'token' => Crypt::decrypt($key->token),
                ]);
            }
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('identifier');
            $table->string('token', 32)->unique()->change();
        });
    }
}
