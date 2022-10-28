<?php

namespace Pterodactyl\Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Login extends Page
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/auth/login';
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url());
    }

    public function loginToPanel(Browser $browser, string $username, string $password)
    {
        $browser->type('username', $username);
        $browser->type('password', $password);

        $browser->clickAndWaitForReload('button[type=submit]', 2);
    }
}
