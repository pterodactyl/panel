<?php

namespace Pterodactyl\Tests\Browser\Pages\Dashboard;

use Pterodactyl\Tests\Browser\Pages\BasePage;

class AccountPage extends BasePage
{
    /**
     * @return string
     */
    public function url()
    {
        return '/account';
    }

    /**
     * @return array
     */
    public function elements()
    {
        return array_merge(parent::elements(), [
            '@email' => '#update-email-container #grid-email',
            '@password' => '#update-email-container #grid-password[type="password"]',
            '@submit' => '#update-email-container button[type="submit"]',

            '@current_password' => '#change-password-container #grid-password-current[type="password"]',
            '@new_password' => '#change-password-container #grid-password-new[type="password"]',
            '@confirm_password' => '#change-password-container #grid-password-new-confirm[type="password"]',
            '@submit_password' => '#change-password-container button[type="submit"]',

            '@2fa_button' => '#grid-open-two-factor-modal',
            '@2fa_modal' => '.modal-mask #configure-two-factor',
            '@2fa_token' => '#configure-two-factor #container-enable-two-factor #grid-two-factor-token[type="number"]',
            '@2fa_token_disable' => '#configure-two-factor #container-disable-two-factor #grid-two-factor-token-disable',
            '@2fa_enable' => '#configure-two-factor #container-enable-two-factor button[type="submit"]',
            '@2fa_disable' => '#configure-two-factor #container-disable-two-factor button.btn-red[type="submit"]',
            '@2fa_cancel' => '#configure-two-factor #container-disable-two-factor button.btn-secondary',
        ]);
    }
}
