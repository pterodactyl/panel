<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Admin\UserController;

use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class DeleteUserTest extends HttpTestCase
{
    public function testNonAdminCannotAccessEndpoint(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('admin.users.delete', ['user' => User::factory()->create()]))
            ->assertForbidden();
    }

    public function testCannotDeleteSelf(): void
    {
        $this->actingAs($user = User::factory()->admin()->create())
            ->delete(route('admin.users.delete', ['user' => $user]))
            ->assertBadRequest()
            ->assertJsonPath('errors.0.detail', __('admin/user.exceptions.delete_self'));

        $this->assertModelExists($user);
    }

    public function testUserIsDeleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.users.delete', ['user' => $user]))
            ->assertRedirectToRoute('admin.users');

        $this->assertModelMissing($user);
    }
}
