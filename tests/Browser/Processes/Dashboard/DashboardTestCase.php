<?php

namespace Pterodactyl\Tests\Browser\Processes\Dashboard;

use Pterodactyl\Tests\Browser\BrowserTestCase;

abstract class DashboardTestCase extends BrowserTestCase
{
    /**
     * @var \Pterodactyl\Models\User
     */
    protected $user;

    /**
     * Setup tests and provide a default user to calling functions.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->user();
    }
}
