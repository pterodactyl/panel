<?php

namespace Tests\Browser\Pages\User;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page as BasePage;

class MyAccount extends BasePage
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/account';
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
        $browser->assertSee(__('base.account.header_sub'));
    }

    public function updateIdentity(Browser $browser, $firstName, $lastName, $username)
    {
        $browser->type('name_first', $firstName)
            ->type('name_last', $lastName)
            ->type('username', $username)
            ->press(__('base.account.update_identity'));
    }
}
