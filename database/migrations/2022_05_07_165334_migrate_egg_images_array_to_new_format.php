<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class MigrateEggImagesArrayToNewFormat extends Migration
{
    /**
     * Run the migrations. This will loop over every egg on the system and update the
     * images array to both exist, and have key => value pairings to support naming the
     * images provided.
     */
    public function up(): void
    {
        DB::table('eggs')->select(['id', 'docker_images'])->cursor()->each(function ($egg) {
            $images = is_null($egg->docker_images) ? [] : json_decode($egg->docker_images, true, 512, JSON_THROW_ON_ERROR);

            $results = [];
            foreach ($images as $key => $value) {
                $results[is_int($key) ? $value : $key] = $value;
            }

            DB::table('eggs')->where('id', $egg->id)->update(['docker_images' => $results]);
        });
    }

    /**
     * Reverse the migrations. This just keeps the values from the docker images array.
     */
    public function down(): void
    {
        DB::table('eggs')->select(['id', 'docker_images'])->cursor()->each(function ($egg) {
            DB::table('eggs')->where('id', $egg->id)->update([
                'docker_images' => array_values(json_decode($egg->docker_images, true, 512, JSON_THROW_ON_ERROR)),
            ]);
        });
    }
}
