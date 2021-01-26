<?php

namespace Pterodactyl\Tests\Browser\Processes\Dashboard;

use PragmaRX\Google2FA\Google2FA;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Tests\Browser\PterodactylBrowser;
use Pterodactyl\Tests\Browser\Pages\Dashboard\AccountPage;

class TwoFactorAuthenticationProcessTest extends DashboardTestCase
{
    /**
     * Test that the modal can be opened and closed.
     */
    public function testModalOpenAndClose()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage())
                ->assertMissing('.modal-mask')
                ->click('@2fa_button')
                ->waitFor('@2fa_modal')
                ->pause(500)// seems to fix fragile test
                ->clickPosition(100, 100)
                ->waitUntilMissing('@2fa_modal')
                ->click('@2fa_button')
                ->waitFor('@2fa_modal')
                ->click('svg[role="button"][aria-label="Close modal"]')
                ->waitUntilMissing('@2fa_modal')
                ->click('@2fa_button')
                ->waitFor('@2fa_modal')
                ->keys('', [WebDriverKeys::ESCAPE])
                ->waitUntilMissing('@2fa_modal');
        });
    }

    /**
     * Test that a user that does not have two-factor enabled can enable it on their account.
     */
    public function testTwoFactorCanBeEnabled()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage())
                ->click('@2fa_button')
                ->waitForText(trans('dashboard/account.two_factor.setup.title'))
                ->assertFocused('@2fa_token')
                ->waitFor('#grid-qr-code')
                ->assertSee(trans('dashboard/account.two_factor.setup.help'));

            // Grab information from the database so we can ensure the correct things are showing up.
            // Also because we need to generate a code to send through and activate it with.
            $updated = $this->user->fresh();

            $secret = Crypt::decrypt($updated->totp_secret);
            $code = (new Google2FA())->getCurrentOtp($secret);

            $browser->assertSeeIn('code', $secret)
                ->assertVisible('@2fa_enable[disabled="disabled"]')
                ->assertMissing('@2fa_token ~ .input-help.error')
                ->type('@2fa_token', '12')
                ->assertSeeIn('@2fa_token ~ .input-help.error', 'The token length must be 6.')
                ->type('@2fa_token', $code)
                ->assertMissing('@2fa_token ~ .input-help.error')
                ->click('@2fa_enable')
                ->waitUntilMissing('@2fa_modal')
                ->assertSeeIn('@@success', trans('dashboard/account.two_factor.enabled'));

            $this->assertDatabaseHas('users', ['id' => $this->user->id, 'use_totp' => 1]);
        });
    }

    /**
     * Test that a user can disable two-factor authentication on thier account.
     */
    public function testTwoFactorCanBeDisabled()
    {
        $secret = (new Google2FA())->generateSecretKey(16);

        $this->user->update([
            'use_totp' => true,
            'totp_secret' => Crypt::encrypt($secret),
        ]);

        $this->browse(function (PterodactylBrowser $browser) use ($secret) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage())
                ->click('@2fa_button')
                ->waitForText(trans('dashboard/account.two_factor.disable.title'))
                ->click('@2fa_cancel')
                ->waitUntilMissing('@2fa_modal')
                ->click('@2fa_button')
                ->waitForText(trans('dashboard/account.two_factor.disable.title'))
                ->assertVisible('@2fa_disable[disabled="disabled"]')
                ->assertVisible('@2fa_cancel')
                ->assertFocused('@2fa_token_disable')
                ->assertMissing('@2fa_token_disable ~ .input-help.error')
                ->type('@2fa_token_disable', '12')
                ->assertSeeIn('@2fa_token_disable ~ .input-help.error', 'The token length must be 6.');

            $token = (new Google2FA())->getCurrentOtp($secret);

            $browser->type('@2fa_token_disable', $token)
                ->assertMissing('@2fa_token_disable ~ .input-help.error')
                ->click('@2fa_disable')
                ->waitUntilMissing('@2fa_modal')
                ->assertSeeIn('@@success', trans('dashboard/account.two_factor.disabled'));
        });
    }
}
