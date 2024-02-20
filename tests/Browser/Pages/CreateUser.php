<?php

namespace Pterodactyl\Tests\Browser\Pages;

use Laravel\Dusk\Page;
use Laravel\Dusk\Browser;

class CreateUser extends Page
{
    public function url()
    {
        return '/admin/users/new';
    }

    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url());
    }

    public function elements()
    {
        return [
            '@submit' => '[type=submit]',
        ];
    }

    public function create(Browser $browser, $email, $username, $password, $firstName, $lastName)
    {
        $browser->type('email', $email);
        $browser->type('username', $username);
        $browser->type('name_first', $firstName);
        $browser->type('name_last', $lastName);
        $browser->type('password', $password);
        $browser->clickAndWaitForReload('@submit', 3);
    }
}
