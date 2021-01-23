<?php

namespace Pterodactyl\Tests\Browser\Processes\Authentication;

use Pterodactyl\Tests\Browser\BrowserTestCase;
use Pterodactyl\Tests\Browser\Pages\LoginPage;
use Pterodactyl\Tests\Browser\PterodactylBrowser;

class ForgotPasswordProcessTest extends BrowserTestCase
{
    /**
     * Test that the password reset page works as expected and displays the expected
     * success messages to the client when submitted.
     */
    public function testResetPasswordWithInvalidAccount()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->visit(new LoginPage())
                ->assertSee(trans('auth.forgot_password.label'))
                ->click('@forgotPassword')
                ->waitForLocation('/auth/password')
                ->assertFocused('@email')
                ->assertSeeIn('.input-open > p.text-xs', trans('auth.forgot_password.label_help'))
                ->assertSeeIn('@submitButton', trans('auth.forgot_password.button'))
                ->type('@email', 'unassociated@example.com')
                ->assertSeeIn('@goToLogin', trans('auth.go_to_login'))
                ->press('@submitButton')
                ->waitForLocation('/auth/login')
                ->assertSeeIn('div[role="alert"].success > span.message', 'We have e-mailed your password reset link!')
                ->assertFocused('@username')
                ->assertValue('@username', 'unassociated@example.com');
        });
    }

    /**
     * Test that you can type in your email address and then click forgot password and have
     * the email maintained on the new page.
     */
    public function testEmailCarryover()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->visit(new LoginPage())
                ->type('@username', 'dane@example.com')
                ->click('@forgotPassword')
                ->waitForLocation('/auth/password')
                ->assertFocused('@email')
                ->assertValue('@email', 'dane@example.com');
        });
    }
}
