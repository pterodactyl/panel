<?php

namespace Pterodactyl\Tests\Browser\Pages;

use Laravel\Dusk\Page;
use Laravel\Dusk\Browser;

class Login extends Page
{
    public function url()
    {
        return '/auth/login';
    }

    public function elements()
    {
        return [
            '@submit' => '[type=submit]',
            '@alert' => '[role=alert]',
        ];
    }

    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url());
    }

    public function submit(Browser $browser, string $username, string $password)
    {
        $browser->type('username', $username);
        $browser->type('password', $password);

        $browser->click('@submit');
    }
}
