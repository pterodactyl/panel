<?php

namespace Pterodactyl\Tests\Browser\Processes\Dashboard;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Tests\Browser\BrowserTestCase;
use Pterodactyl\Tests\Browser\PterodactylBrowser;
use Pterodactyl\Tests\Browser\Pages\Dashboard\AccountPage;

class AccountEmailProcessTest extends BrowserTestCase
{
    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    /**
     * Setup a user for the test process to use.
     */
    public function setUp()
    {
        parent::setUp();

        $this->user = factory(User::class)->create([
            'password' => Hash::make('Password123'),
        ]);
    }

    public function testEmailCanBeChanged()
    {
        $this->browse(function (PterodactylBrowser $browser) {
            $browser->loginAs($this->user)
                ->visit(new AccountPage)
                ->assertValue('@email', $this->user->email)
                ->type('@email', 'new.email@example.com')
                ->type('@password', 'Password123')
                ->click('@submit')
                ->waitFor('@@success')
                ->assertSeeIn('@@success', trans('dashboard/account.email.updated'))
                ->assertValue('@email', 'new.email@example.com');

            $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'new.email@example.com']);
        });
    }
}
