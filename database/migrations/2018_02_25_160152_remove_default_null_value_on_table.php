<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDefaultNullValueOnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('external_id')->default(null)->change();
        });

        DB::transaction(function () {
            DB::table('users')->where('external_id', '=', 'NULL')->update([
                'external_id' => null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This should not be rolled back.
    }
}
