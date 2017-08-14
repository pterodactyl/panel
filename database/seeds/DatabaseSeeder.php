<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(MinecraftServiceTableSeeder::class);
        $this->call(SourceServiceTableSeeder::class);
        $this->call(RustServiceTableSeeder::class);
        $this->call(TerrariaServiceTableSeeder::class);
        $this->call(VoiceServiceTableSeeder::class);

        Model::reguard();
    }
}
