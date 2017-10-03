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

            $table->index(['service_id', 'tag']);
        });

        DB::transaction(function () {
            DB::table('service_options')->select([
                'service_options.id',
                'service_options.uuid',
                'service_options.tag',
                'services.author AS service_author',
            ])->join('services', 'services.id', '=', 'service_options.service_id')->get()->each(function ($option) {
                DB::table('service_options')->where('id', $option->id)->update([
                    'tag' => $option->service_author . ':' . $option->tag,
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
            $table->dropIndex(['service_id', 'tag']);
        });

        DB::transaction(function () {
            DB::table('service_options')->select(['id', 'author'])->get()->each(function ($option) {
                DB::table('service_options')->where('id', $option->id)->update([
                    'tag' => array_get(explode(':', $option->tag), 1),
                ]);
            });
        });
    }
}
