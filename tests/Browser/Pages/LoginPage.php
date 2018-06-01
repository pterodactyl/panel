<?php

namespace Pterodactyl\Tests\Browser\Pages;

class LoginPage extends BasePage
{
    /**
     * @return string
     */
    public function url(): string
    {
        return '/auth/login';
    }

    public function elements()
    {
        return [
            '@username' => '#grid-username',
            '@password' => '#grid-password',
            '@loginButton' => '#grid-login-button',
            '@forgotPassword' => 'a[aria-label="Forgot password"]',
        ];
    }
}
