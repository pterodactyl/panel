<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Mockery as m;
use Mockery\MockInterface;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\TestCase;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Auth\DirectLogin;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\StatefulGuard;
use Pterodactyl\Services\Users\UserCreationService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Middleware\AuthenticateFromTrustedHeader;

class AuthenticateFromTrustedHeaderTest extends TestCase
{
    private MockInterface $auth;
    private MockInterface $guard;
    private MockInterface $users;
    private MockInterface $userCreation;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock(AuthManager::class);
        $this->guard = m::mock(StatefulGuard::class);
        $this->users = m::mock(UserRepositoryInterface::class);
        $this->userCreation = m::mock(UserCreationService::class);

        config([
            'auth.header.enabled' => true,
            'auth.header.auto_create' => false,
            'auth.header.username_header' => 'X-Auth-Username',
            'auth.header.email_header' => 'X-Auth-Email',
            'trustedproxy.proxies' => ['10.0.0.2'],
        ]);

        Event::fake([DirectLogin::class]);
    }

    public function testDisabledHeaderAuthenticationIsSkipped(): void
    {
        config(['auth.header.enabled' => false]);

        $response = $this->middleware()->handle($this->request(), static fn () => 'next');

        $this->assertSame('next', $response);
    }

    public function testAlreadyAuthenticatedRequestIsSkipped(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnTrue();

        $response = $this->middleware()->handle($this->request('alice', 'alice@example.com'), static fn () => 'next');

        $this->assertSame('next', $response);
    }

    public function testMissingRemoteHeadersContinueNormally(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();

        $response = $this->middleware()->handle($this->request(), static fn () => 'next');

        $this->assertSame('next', $response);
    }

    public function testPartialHeadersAreRejected(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();

        try {
            $this->middleware()->handle($this->request('alice', ''), static fn () => 'next');
            $this->fail('Expected HTTP exception for partial headers.');
        } catch (HttpException $exception) {
            $this->assertSame(400, $exception->getStatusCode());
        }
    }

    public function testUntrustedProxyIsRejected(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();

        try {
            $this->middleware()->handle(
                $this->request('alice', 'alice@example.com', '203.0.113.10'),
                static fn () => 'next',
            );
            $this->fail('Expected HTTP exception for untrusted proxy.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function testExistingUserIsLoggedInFromTrustedProxy(): void
    {
        $user = User::factory()->make([
            'username' => 'alice',
            'email' => 'alice@example.com',
        ]);

        $session = m::mock(Session::class);
        $session->shouldReceive('forget')->once()->with('auth_confirmation_token');
        $session->shouldReceive('regenerate')->once()->with(true);

        $request = $this->request('alice', 'alice@example.com');
        $request->setLaravelSession($session);

        $this->auth->shouldReceive('guard')->twice()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->users->shouldReceive('findFirstWhere')->once()->with(['email' => 'alice@example.com'])->andReturn($user);
        $this->guard->shouldReceive('login')->once()->with($user, false);

        $response = $this->middleware()->handle($request, static fn () => 'next');

        $this->assertSame('next', $response);
        Event::assertDispatched(DirectLogin::class, function (DirectLogin $event) use ($user) {
            return $event->user === $user && $event->remember === false;
        });
    }

    public function testUsernameEmailCollisionIsRejected(): void
    {
        $user = User::factory()->make([
            'username' => 'alice',
            'email' => 'other@example.com',
        ]);

        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->users->shouldReceive('findFirstWhere')->once()->with(['email' => 'alice@example.com'])->andThrow(new RecordNotFoundException());
        $this->users->shouldReceive('findFirstWhere')->once()->with(['username' => 'alice'])->andReturn($user);

        try {
            $this->middleware()->handle($this->request('alice', 'alice@example.com'), static fn () => 'next');
            $this->fail('Expected HTTP exception for identity collision.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
    }

    public function testUnknownUserIsRejectedWhenAutoCreateDisabled(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->users->shouldReceive('findFirstWhere')->once()->with(['email' => 'alice@example.com'])->andThrow(new RecordNotFoundException());
        $this->users->shouldReceive('findFirstWhere')->once()->with(['username' => 'alice'])->andThrow(new RecordNotFoundException());

        try {
            $this->middleware()->handle($this->request('alice', 'alice@example.com'), static fn () => 'next');
            $this->fail('Expected HTTP exception when auto-create is disabled.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function testUnknownUserIsCreatedWhenAutoCreateEnabled(): void
    {
        config(['auth.header.auto_create' => true]);

        $user = User::factory()->make([
            'username' => 'alice',
            'email' => 'alice@example.com',
        ]);

        $session = m::mock(Session::class);
        $session->shouldReceive('forget')->once()->with('auth_confirmation_token');
        $session->shouldReceive('regenerate')->once()->with(true);

        $request = $this->request('alice', 'alice@example.com');
        $request->setLaravelSession($session);

        $this->auth->shouldReceive('guard')->twice()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->users->shouldReceive('findFirstWhere')->once()->with(['email' => 'alice@example.com'])->andThrow(new RecordNotFoundException());
        $this->users->shouldReceive('findFirstWhere')->once()->with(['username' => 'alice'])->andThrow(new RecordNotFoundException());
        $this->userCreation->shouldReceive('handle')->once()->with([
            'username' => 'alice',
            'email' => 'alice@example.com',
            'name_first' => 'alice',
            'name_last' => 'User',
        ])->andReturn($user);
        $this->guard->shouldReceive('login')->once()->with($user, false);

        $response = $this->middleware()->handle($request, static fn () => 'next');

        $this->assertSame('next', $response);
        Event::assertDispatched(DirectLogin::class);
    }

    private function middleware(): AuthenticateFromTrustedHeader
    {
        return new AuthenticateFromTrustedHeader($this->auth, $this->users, $this->userCreation);
    }

    private function request(string $username = '', string $email = '', string $remoteAddr = '10.0.0.2'): Request
    {
        $request = Request::create('/', 'GET');
        $request->server->set('REMOTE_ADDR', $remoteAddr);

        if ($username !== '') {
            $request->headers->set('X-Auth-Username', $username);
        }
        if ($email !== '') {
            $request->headers->set('X-Auth-Email', $email);
        }

        return $request;
    }
}
