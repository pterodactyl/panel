<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Admin\UserController;

use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class CreateUserTest extends HttpTestCase
{
    public function testNonAdminCannotAccessEndpoint(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/users/new', [
                'email' => 'test@example.com',
                'username' => 'testuser',
                'name_first' => 'Test',
                'name_last' => 'User',
            ])
            ->assertForbidden();
    }

    public function testCreatingAdministratorAccountIsLogged(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/users/new', [
                'email' => 'created.admin@example.com',
                'username' => 'createdadmin',
                'name_first' => 'Created',
                'name_last' => 'Admin',
                'root_admin' => 1,
            ])
            ->assertSessionHasNoErrors();

        /** @var User $created */
        $created = User::query()->where('username', 'createdadmin')->firstOrFail();
        $this->assertTrue($created->root_admin);

        $this->assertActivityFor('user:user.create', $admin, $created);
    }
}
