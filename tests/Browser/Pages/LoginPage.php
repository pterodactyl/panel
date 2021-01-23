<?php

namespace Pterodactyl\Tests\Browser\Pages;

class LoginPage extends BasePage
{
    public function url(): string
    {
        return '/auth/login';
    }

    public function elements()
    {
        return [
            '@email' => '#grid-email',
            '@username' => '#grid-username',
            '@password' => '#grid-password',
            '@loginButton' => '#grid-login-button',
            '@submitButton' => 'button.btn.btn-jumbo[type="submit"]',
            '@forgotPassword' => 'a[href="/auth/password"][aria-label="Forgot password"]',
            '@goToLogin' => 'a[href="/auth/login"][aria-label="Go to login"]',
            '@alertSuccess' => 'div[role="alert"].success > span.message',
            '@alertDanger' => 'div[role="alert"].danger > span.message',
        ];
    }
}
