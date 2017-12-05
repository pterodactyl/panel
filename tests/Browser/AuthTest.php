<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Pterodactyl\Models\User;
use Tests\Traits\DatabaseTruncations;

class AuthTest extends DuskTestCase
{
    use DatabaseTruncations;

    public function testLoginFailsWithInvalidCredentials()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->type('user', 'fake')
                ->type('password', 'fake')
                ->press('Sign In')
                ->assertSee('There was an error while attempting to login');
        });
    }

    public function testLoginSucceedsWithValidCredentialsAndCanLogout()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/')
                ->assertPathIs('/auth/login')
                ->type('user', $user->email)
                ->type('password', 'password')
                ->press('Sign In')
                ->assertPathIs('/')
                ->assertSee($user->name_first)
                ->click('#logoutButton')
                ->assertPathIs('/auth/login')
                ->assertSee('Authentication is required to continue.')
                ->visit('/account')
                ->assertPathIs('/auth/login');
        });
    }
}
