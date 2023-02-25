<?php

namespace Database\Seeders\Seeds;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()
            ->count(10)
            ->has(Server::factory()->count(rand(1, 3)), 'servers')
            ->create();
    }
}
