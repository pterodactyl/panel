<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;
use Illuminate\Pagination\LengthAwarePaginator;
use Pterodactyl\Http\Controllers\Admin\UserController;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

class UserControllerTest extends IntegrationTestCase
{
    /**
     * Test that the index route controller for the user listing returns the expected user
     * data with the number of servers they are assigned to, and the number of servers they
     * are a subuser of.
     *
     * @see https://github.com/pterodactyl/panel/issues/2469
     */
    public function testIndexReturnsExpectedData()
    {
        $unique = Str::random();
        $users = [
            User::factory()->create(['username' => $unique . '_1']),
            User::factory()->create(['username' => $unique . '_2']),
        ];

        $servers = [
            $this->createServerModel(['owner_id' => $users[0]->id]),
            $this->createServerModel(['owner_id' => $users[0]->id]),
            $this->createServerModel(['owner_id' => $users[0]->id]),
            $this->createServerModel(['owner_id' => $users[1]->id]),
        ];

        Subuser::query()->forceCreate(['server_id' => $servers[0]->id, 'user_id' => $users[1]->id]);
        Subuser::query()->forceCreate(['server_id' => $servers[1]->id, 'user_id' => $users[1]->id]);

        /** @var \Pterodactyl\Http\Controllers\Admin\UserController $controller */
        $controller = $this->app->make(UserController::class);

        $request = Request::create('/admin/users?filter[username]=' . $unique);
        $this->app->instance(Request::class, $request);

        $data = $controller->index($request)->getData();
        $this->assertArrayHasKey('users', $data);
        $this->assertInstanceOf(LengthAwarePaginator::class, $data['users']);

        /** @var \Pterodactyl\Models\User[] $response */
        $response = $data['users']->items();
        $this->assertCount(2, $response);
        $this->assertInstanceOf(User::class, $response[0]);
        $this->assertSame(3, (int) $response[0]->servers_count);
        $this->assertSame(0, (int) $response[0]->subuser_of_count);
        $this->assertSame(1, (int) $response[1]->servers_count);
        $this->assertSame(2, (int) $response[1]->subuser_of_count);
    }
}
