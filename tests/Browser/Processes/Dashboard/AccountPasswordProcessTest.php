<?php

namespace Pterodactyl\Tests\Browser\Processes\Dashboard;

use Pterodactyl\Tests\Browser\PterodactylBrowser;
use Pterodactyl\Tests\Browser\Pages\Dashboard\AccountPage;

class AccountPasswordProcessTest extends DashboardTestCase
{
    /**
     * Test that a user is able to change their password.
     */
    public function testPasswordCanBeChanged()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage())
                ->type('@current_password', self::$userPassword)
                ->assertMissing('@new_password ~ .input-help.error')
                ->type('@new_password', 'test')
                ->assertSeeIn('@new_password ~ .input-help.error', 'The password field must be at least 8 characters.')
                ->type('@new_password', 'Test1234')
                ->assertMissing('@new_password ~ .input-help.error')
                ->assertMissing('@confirm_password ~ .input-help.error')
                ->type('@confirm_password', 'test')
                ->assertSeeIn('@confirm_password ~ .input-help.error', 'The password value is not valid.')
                ->type('@confirm_password', 'Test1234')
                ->assertMissing('@confirm_password ~ .input-help.error')
                ->click('@submit_password')
                ->waitFor('@@success')
                ->assertSeeIn('@@success', 'Your password has been updated.')
                ->assertInputValue('@current_password', '')
                ->assertInputValue('@new_password', '')
                ->assertInputValue('@confirm_password', '');
        });
    }

    /**
     * Test that invalid passwords result in the expected error message.
     */
    public function testInvalidPassword()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage())
                ->type('@current_password', 'badpassword')
                ->type('@new_password', 'testtest')
                ->type('@confirm_password', 'testtest')
                ->click('@submit_password')
                ->waitFor('@@error')
                ->assertSeeIn('@@error', trans('validation.internal.invalid_password'))
                ->assertInputValue('@current_password', '');
        });
    }
}
