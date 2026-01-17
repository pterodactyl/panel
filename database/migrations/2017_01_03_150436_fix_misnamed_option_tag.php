<?php

use Illuminate\Database\Migrations\Migration;

class FixMisnamedOptionTag extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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
    public function down(): void
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
