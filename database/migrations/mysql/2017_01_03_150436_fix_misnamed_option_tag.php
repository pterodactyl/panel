<?php

use Illuminate\Database\Migrations\Migration;

class FixMisnamedOptionTag extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::transaction(function () {
            DB::table('service_options')->where([
                ['name', 'Sponge (SpongeVanilla)'],
                ['tag', 'spigot'],
                ['docker_image', 'quay.io/pterodactyl/minecraft:sponge'],
            ])->update([
                'tag' => 'sponge',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('service_options')->where([
            ['name', 'Sponge (SpongeVanilla)'],
            ['tag', 'sponge'],
            ['docker_image', 'quay.io/pterodactyl/minecraft:sponge'],
        ])->update([
            'tag' => 'spigot',
        ]);
    }
}
