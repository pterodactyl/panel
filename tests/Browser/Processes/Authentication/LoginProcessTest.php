<?php

namespace Pterodactyl\Tests\Browser\Processes\Authentication;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Hash;
use Facebook\WebDriver\WebDriverKeys;
use Pterodactyl\Tests\Browser\BrowserTestCase;
use Pterodactyl\Tests\Browser\Pages\LoginPage;
use Pterodactyl\Tests\Browser\PterodactylBrowser;

class LoginProcessTest extends BrowserTestCase
{
    private $user;

    /**
     * Setup tests.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->user = factory(User::class)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123'),
        ]);
    }

    /**
     * Test that a user can login successfully using their email address.
     */
    public function testLoginUsingEmail()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->visit(new LoginPage)
                ->waitFor('@username')
                ->type('@username', 'test@example.com')
                ->type('@password', 'Password123')
                ->click('@loginButton')
                ->waitForReload()
                ->assertPathIs('/')
                ->assertAuthenticatedAs($this->user);
        });
    }

    /**
     * Test that a user can login successfully using their username.
     */
    public function testLoginUsingUsername()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->visit(new LoginPage)
                ->waitFor('@username')
                ->type('@username', $this->user->username)
                ->type('@password', 'Password123')
                ->click('@loginButton')
                ->waitForReload()
                ->assertPathIs('/')
                ->assertAuthenticatedAs($this->user);
        });
    }

    /**
     * Test that entering the wrong password shows the expected error and then allows
     * us to login without clearing the username field.
     */
    public function testLoginWithErrors()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->logout()
                ->visit(new LoginPage())
                ->waitFor('@username')
                ->type('@username', 'test@example.com')
                ->type('@password', 'invalid')
                ->click('@loginButton')
                ->waitFor('.alert.error')
                ->assertSeeIn('.alert.error', trans('auth.failed'))
                ->assertValue('@username', 'test@example.com')
                ->assertValue('@password', '')
                ->assertFocused('@password')
                ->type('@password', 'Password123')
                ->keys('@password', [WebDriverKeys::ENTER])
                ->waitForReload()
                ->assertPathIs('/')
                ->assertAuthenticatedAs($this->user);
        });
    }
}
