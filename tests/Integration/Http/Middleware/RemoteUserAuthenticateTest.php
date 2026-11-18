<?php

namespace Pterodactyl\Tests\Integration\Http\Middleware;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class RemoteUserAuthenticateTest extends HttpTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/__test/remote-user', function () {
            return response()->json(['authenticated' => Auth::check(), 'user' => Auth::user()?->username]);
        });

        config()->set('pterodactyl.auth.remote_user.enabled', true);
        config()->set('pterodactyl.auth.remote_user.user_header', 'X-Remote-User');
        config()->set('pterodactyl.auth.remote_user.email_header', 'X-Remote-Email');
        config()->set('pterodactyl.auth.remote_user.create_users', true);
    }

    public function testRequestWithoutHeadersIsNotAuthenticated(): void
    {
        $this->get('/__test/remote-user')
            ->assertOk()
            ->assertJson(['authenticated' => false]);
    }

    public function testExistingUserIsAuthenticatedByHeader(): void
    {
        $user = User::factory()->create();

        $this->withHeader('X-Remote-User', $user->username)
            ->get('/__test/remote-user')
            ->assertOk()
            ->assertJson(['authenticated' => true, 'user' => $user->username]);
    }

    public function testUnknownUserIsCreatedAndAuthenticated(): void
    {
        $this->withHeaders([
            'X-Remote-User' => 'ProxyUser',
            'X-Remote-Email' => 'proxy@example.com',
        ])->get('/__test/remote-user')
            ->assertOk()
            ->assertJson(['authenticated' => true, 'user' => 'proxyuser']);

        $this->assertDatabaseHas('users', [
            'username' => 'proxyuser',
            'email' => 'proxy@example.com',
            'root_admin' => false,
        ]);
    }

    public function testEmailIsDerivedWhenEmailHeaderMissing(): void
    {
        $this->withHeader('X-Remote-User', 'NoEmail')
            ->get('/__test/remote-user')
            ->assertOk()
            ->assertJson(['authenticated' => true, 'user' => 'noemail']);

        $this->assertDatabaseHas('users', ['username' => 'noemail']);
    }

    public function testUsernameIsDerivedFromEmailStyleHeader(): void
    {
        $this->withHeaders([
            'X-Remote-User' => 'Jane.Doe@example.com',
            'X-Remote-Email' => 'jane.doe@example.com',
        ])->get('/__test/remote-user')
            ->assertOk()
            ->assertJson(['authenticated' => true, 'user' => 'jane.doe']);
    }

    public function testCreationDeniedWhenAutoCreateDisabled(): void
    {
        config()->set('pterodactyl.auth.remote_user.create_users', false);

        $this->withHeader('X-Remote-User', 'NewUser')
            ->get('/__test/remote-user')
            ->assertForbidden();
    }

    public function testMiddlewareIsInertWhenDisabled(): void
    {
        config()->set('pterodactyl.auth.remote_user.enabled', false);

        $this->withHeader('X-Remote-User', 'AnyUser')
            ->get('/__test/remote-user')
            ->assertOk()
            ->assertJson(['authenticated' => false]);
    }
}
