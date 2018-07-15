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
            '@password' => '#update-email-container #grid-password',
            '@submit' => '#update-email-container button[type="submit"]',
        ]);
    }
}
