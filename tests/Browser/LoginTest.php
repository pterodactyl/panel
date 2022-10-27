<?php

namespace Pterodactyl\Tests\Browser;

use Laravel\Dusk\Browser;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\DuskTestCase;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Tests\Browser\Pages\Login;
// use Pterodactyl\Tests\Traits\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testLogin()
    {
        $login = 'testing@pterodactyl.io';
        $pass = 'password';

        $user = User::factory()->create([
            'email' => $login,
            'password' => Hash::make($pass),
        ]);

        $this->browse(function (Browser $browser) use ($login, $pass) {
            $browser->visit(new Login())->loginToPanel($browser, $login, $pass);
        });
    }
}
