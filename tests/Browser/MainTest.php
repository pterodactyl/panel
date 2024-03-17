<?php

namespace Pterodactyl\Tests\Browser;

use Laravel\Dusk\Browser;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\DuskTestCase;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Tests\Browser\Pages\Login;
use Pterodactyl\Tests\Browser\Pages\CreateUser;
use Pterodactyl\Tests\Traits\DatabaseMigrations;

class MainTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testEverything()
    {
        $login = 'lance@pterodactyl.io';
        $pass = 'password';

        // Create Administrator
        $user = User::factory()->create([
            'email' => $login,
            'password' => Hash::make($pass),
            'root_admin' => true,
            'name_first' => 'Lance',
            'name_last' => 'Dactyl',
        ]);

        // Seed initial eggs
        $this->artisan('migrate --seed --force');

        $this->browse(function (Browser $browser) use ($login, $pass) {
            [$panelProtocol, $panelUrl] = explode('://', config('app.url'), 2);

            $panelDomain = $panelUrl;
            if (str_contains($panelUrl, ':')) {
                [$panelDomain, $panelPort] = explode(':', $panelUrl, 2);
            }

            // Default to HTTP if not specified
            $panelPort = intval($panelPort ?? 80);

            // For CI, use next port
            if ($panelPort !== 80) {
                ++$panelPort;
            }

            // Test Failed Login
            $browser->visit(new Login())
                ->submit($login, 'incorrect')
                ->waitFor('@alert', 2)
                ->assertSeeIn('@alert', 'ERROR');

            // Test Successful Login
            $browser->visit(new Login())
                ->submit($login, $pass)
                ->waitForReload()
                ->assertPathIs('/');

            // Test No Servers
            $browser->assertMissing('section div>a');

            // Click on Admin Dashboard /admin and see no redirect or not denied access
            $browser->visit('/admin');
            $browser->assertPathIs('/admin');
            $browser->assertDontSee('Forbidden');
            $browser->assertSee('Admin');

            // Create new non administrator user and see success
            $browser->visit(new CreateUser())
                ->create('matthew@example.com', 'bird', 'mypasswordiscooler', 'Matthew', 'Dactyl')
                ->assertPathIs('/admin/users/view/2');

            // Try to create duplicate user and see failure
            $browser->visit(new CreateUser())
                ->create('matthew@example.com', 'bird', 'mypasswordiscool', 'Matthew', 'Dactyl')
                ->assertSee('There was an error')
                ->assertPathIs('/admin/users/new');

            // Click on Locations in navigation and then click on Create New
            $browser->visit('/admin/locations');
            $browser->assertSee('Create New');
            $browser->click('button[data-target="#newLocationModal"]');
            $browser->waitFor('.modal-dialog', 3);

            // Create New Location successfully
            $browser->type('short', 'us');
            $browser->type('long', 'Number one exporter of potassium');
            $browser->clickAndWaitForReload('button[type=submit]');
            $browser->assertPathIs('/admin/locations/view/1');

            // Click on Nodes in navigation and then create a new Node successfully
            $browser->visit('/admin/nodes/new');
            $browser->type('name', 'noderize');
            $browser->type('description', 'my server is the best');
            $browser->select('location_id', '1');
            $browser->type('fqdn', $panelDomain);
            $browser->click('label[for=pSSLFalse]'); // radio http
            $browser->type('memory', '1024');
            $browser->type('memory_overallocate', '0');
            $browser->type('disk', '1024');
            $browser->type('disk_overallocate', '0');
            $browser->type('daemonListen', $panelPort);
            $browser->clickAndWaitForReload('button[type=submit]');
            $browser->assertPathIs('/admin/nodes/view/1/allocation');

            // Create 3 new dummy allocations successfully in the same Node
            $browser->waitForText('Assign New Allocations');
            $browser->type('select[name="allocation_ip"] + span.select2 input[type="search"]', '127.0.0.1');
            $browser->type('select[name="allocation_ports[]"] + span.select2 input[type="search"]', '1234 ');
            $browser->type('select[name="allocation_ports[]"] + span.select2 input[type="search"]', '2345 ');
            $browser->type('select[name="allocation_ports[]"] + span.select2 input[type="search"]', '3456');
            $browser->clickAndWaitForReload('button[type=submit]');
            $browser->assertPathIs('/admin/nodes/view/1/allocation');
            $browser->assertSeeIn('table', '1234');
            $browser->assertSeeIn('table', '2345');
            $browser->assertSeeIn('table', '3456');

            // See that the heartbeat is green/success
            $browser->visit('/admin/nodes');
            $browser->waitFor('table .fa-heartbeat', 5);

            // Create New Node successfully
            $browser->visit('/admin/nodes/new');
            $browser->type('name', 'antinode');
            $browser->type('description', 'my server broke :(');
            $browser->select('location_id', '1');
            $browser->type('fqdn', $panelDomain);
            $browser->click('label[for=pSSLFalse]'); // radio http
            $browser->type('memory', '1024');
            $browser->type('memory_overallocate', '0');
            $browser->type('disk', '1024');
            $browser->type('disk_overallocate', '0');
            $browser->type('daemonListen', '9001');
            $browser->clickAndWaitForReload('button[type=submit]');
            $browser->assertPathIs('/admin/nodes/view/2/allocation');

            // Go back to /admin/nodes and see the heartbeat is red/failing
            $browser->visit('/admin/nodes');
            $browser->waitFor('table .fa-heart-o', 5);

            $servers = [
                'names' => ['apple', 'banana', 'cherry'],
                'owners' => ['Lance', 'Lance', 'Matthew'],
            ];

            // Create 3 New Servers successfully
            for ($i = 0; $i < 3; ++$i) {
                // Click on Servers in navigation and then click on Create New
                $browser->visit('/admin/servers/new');
                $browser->type('name', $servers['names'][$i]);
                $browser->click('select[name=owner_id] + .select2');
                $browser->waitFor('script + .select2-container input[type=search]');
                $browser->type('script + .select2-container input[type=search]', $servers['owners'][$i]);
                $browser->waitForTextIn('.username', $servers['owners'][$i], 3);
                $browser->click('.user-block');
                $browser->type('description', 'Yay a server');
                $browser->type('memory', '1024');
                $browser->type('disk', '1024');
                $browser->clickAndWaitForReload('input[type=submit]');
                $browser->assertPathIs('/admin/servers/view/' . ($i + 1));
            }

            // Exit Admin Panel and see two servers
            $browser->visit('/');
            $browser->waitForText('SERVERS');
            $browser->assertSee('apple');
            $browser->assertSee('banana');
            $browser->assertDontSee('cherry');
            $browser->assertDontSee('There are no other servers to display.');

            // Click the toggle and see the final one not owned by the admin
            $browser->click('input[name=show_all_servers] + label');
            $browser->waitForText('cherry');
            $browser->assertSee('cherry');
            $browser->assertDontSee('apple');
            $browser->assertDontSee('banana');

            // Switch back to the owned servers
            $browser->click('input[name=show_all_servers] + label');
            $browser->waitForText('banana');

            /** @var Server $server */
            $server = Server::query()->findOrFail(2);
            $server->update(['status' => null]);

            // Click on the middle server and then click on Users in the navigation
            $browser->click("a[href='/server/$server->uuidShort']");
            $browser->waitForText('banana');
            $browser->click("a[href='/server/$server->uuidShort/users']");
            $browser->waitForText("It looks like you don't have any subusers.");

            // Click on New User and enter the same email as the non admin user (full permissions)
            $browser->click('section button');
            $browser->waitForText('Create new subuser');
            $browser->click('input[type=checkbox]');
            $browser->type('email', 'matthew@example.com');
            $browser->assertDontSee('A valid email address must be provided.');
            $browser->click('button[type=submit]');
            $browser->waitFor('button[aria-label="Edit subuser"]');
            $browser->assertSee('matthew@example.com');

            // Click on logout and see redirect back to login screen
            $browser->clickAndWaitForReload('#logo + div button');
            $browser->assertPathIs('/auth/login');

            // Login as the non admin user successfully
            $browser->type('username', 'matthew@example.com');
            $browser->type('password', 'mypasswordiscooler');
            $browser->clickAndWaitForReload('button[type=submit]');
            $browser->assertPathIs('/');
            $browser->waitForText('127.0.0.1');

            // See both owned server and unowned
            $browser->assertDontSee('apple');
            $browser->assertSee('banana');
            $browser->assertSee('cherry');
        });
    }
}
