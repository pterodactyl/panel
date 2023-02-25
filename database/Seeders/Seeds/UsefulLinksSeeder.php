<?php

namespace Database\Seeders\Seeds;

use App\Models\UsefulLink;
use Illuminate\Database\Seeder;

class UsefulLinksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UsefulLink::create([
            'icon' => 'fas fa-egg',
            'title' => 'Pterodactyl Panel',
            'link' => env('PTERODACTYL_URL', 'http://localhost'),
            'description' => 'Use your servers on our pterodactyl panel <small>(You can use the same login details)</small>',
            'position' => 'dashboard',
        ]);
        UsefulLink::create([
            'icon' => 'fas fa-database',
            'title' => 'phpMyAdmin',
            'link' => env('PHPMYADMIN_URL', 'http://localhost'),
            'description' => 'View your database online using phpMyAdmin',
            'position' => 'dashboard,topbar',
        ]);
        UsefulLink::create([
            'icon' => 'fab fa-discord',
            'title' => 'Discord',
            'link' => env('DISCORD_INVITE_URL', 'https://discord.gg/4Y6HjD2uyU'),
            'description' => 'Need a helping hand? Want to chat? Got any questions? Join our discord!',
            'position' => 'dashboard',
        ]);
    }
}
