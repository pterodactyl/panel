<?php

namespace Pterodactyl\Tests\Browser\Processes\Dashboard;

use Pterodactyl\Tests\Browser\BrowserTestCase;
use Pterodactyl\Tests\Browser\PterodactylBrowser;
use Pterodactyl\Tests\Browser\Pages\Dashboard\AccountPage;

class AccountEmailProcessTest extends BrowserTestCase
{
    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    /**
     * Setup tests.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->user = $this->user();
    }

    /**
     * Test that an email address can be changed successfully.
     */
    public function testEmailCanBeChanged()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage)
                ->assertValue('@email', $this->user->email)
                ->type('@email', 'new.email@example.com')
                ->type('@password', 'Password123')
                ->click('@submit')
                ->waitFor('@@success')
                ->assertSeeIn('@@success', trans('dashboard/account.email.updated'))
                ->assertValue('@email', 'new.email@example.com');

            $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'new.email@example.com']);
        });
    }

    /**
     * Test that entering the wrong password for an account returns an error.
     */
    public function testInvalidPasswordShowsError()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage)
                ->type('@email', 'new.email@example.com')
                ->click('@submit')
                ->assertFocused('@password')
                ->type('@password', 'test1234')
                ->click('@submit')
                ->waitFor('@@error')
                ->assertSeeIn('@@error', trans('validation.internal.invalid_password'))
                ->assertValue('@email', 'new.email@example.com');

            $this->assertDatabaseMissing('users', ['id' => $this->user->id, 'email' => 'new.email@example.com']);
        });
    }
}
