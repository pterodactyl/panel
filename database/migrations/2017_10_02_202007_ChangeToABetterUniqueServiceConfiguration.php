<?php

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeToABetterUniqueServiceConfiguration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->char('uuid', 36)->after('id');
            $table->dropColumn('tag');
        });

        DB::transaction(function () {
            DB::table('service_options')->select([
                'service_options.id',
                'service_options.uuid',
                'services.author AS service_author',
            ])->join('services', 'services.id', '=', 'service_options.service_id')->get()->each(function ($option) {
                DB::table('service_options')->where('id', $option->id)->update([
                    'uuid' => Uuid::uuid4()->toString(),
                ]);
            });
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropColumn('uuid');
            $table->string('tag');
        });

        DB::transaction(function () {
            DB::table('service_options')->select(['id', 'tag'])->get()->each(function ($option) {
                DB::table('service_options')->where('id', $option->id)->update([
                    'tag' => str_random(10),
                ]);
            });
        });
    }
}
