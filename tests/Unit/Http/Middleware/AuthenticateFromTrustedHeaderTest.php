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
    private MockInterface $repository;
    private MockInterface $creationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock(AuthManager::class);
        $this->guard = m::mock(StatefulGuard::class);
        $this->repository = m::mock(UserRepositoryInterface::class);
        $this->creationService = m::mock(UserCreationService::class);

        config([
            'auth.header.enabled' => true,
            'auth.header.auto_create' => false,
            'auth.header.username_header' => 'Remote-User',
            'auth.header.email_header' => 'Remote-Email',
            'trustedproxy.proxies' => ['10.0.0.2'],
        ]);

        Event::fake([DirectLogin::class]);
    }

    public function testDisabledHeaderAuthenticationIsSkipped(): void
    {
        config(['auth.header.enabled' => false]);

        $response = $this->middleware()->handle($this->request(), fn (Request $request) => 'next');

        $this->assertSame('next', $response);
    }

    public function testRequestWithoutRemoteHeadersUsesNormalAuthenticationFlow(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();

        $response = $this->middleware()->handle($this->request(), fn (Request $request) => 'next');

        $this->assertSame('next', $response);
    }

    public function testHeadersFromUntrustedAddressAreRejected(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();

        try {
            $this->middleware()->handle(
                $this->request('alice', 'alice@example.com', '203.0.113.10'),
                fn (Request $request) => 'next',
            );

            $this->fail('Expected an HTTP exception for untrusted remote headers.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function testExistingUserIsLoggedInFromTrustedHeaders(): void
    {
        $user = User::factory()->make(['username' => 'alice', 'email' => 'alice@example.com']);
        $session = $this->sessionExpectingRegeneration();

        $this->auth->shouldReceive('guard')->twice()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->guard->shouldReceive('login')->once()->with($user);
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['email' => 'alice@example.com'])
            ->andReturn($user);

        $response = $this->middleware()->handle(
            $this->request('alice', 'Alice@Example.com', '10.0.0.2', $session),
            fn (Request $request) => 'next',
        );

        $this->assertSame('next', $response);
        Event::assertDispatched(fn (DirectLogin $event) => $event->user === $user && $event->remember === false);
    }

    public function testEmailShapedRemoteUserDerivesCredentials(): void
    {
        $user = User::factory()->make(['username' => 'alice', 'email' => 'alice@example.com']);
        $session = $this->sessionExpectingRegeneration();

        $this->auth->shouldReceive('guard')->twice()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->guard->shouldReceive('login')->once()->with($user);
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['email' => 'alice@example.com'])
            ->andReturn($user);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.0.0.2']);
        $request->headers->set('Remote-User', 'alice@example.com');
        $request->setLaravelSession($session);

        $response = $this->middleware()->handle($request, fn (Request $request) => 'next');

        $this->assertSame('next', $response);
    }

    public function testMissingLocalAccountIsRejectedWhenAutoCreateDisabled(): void
    {
        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['email' => 'alice@example.com'])
            ->andThrow(new RecordNotFoundException());
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['username' => 'alice'])
            ->andThrow(new RecordNotFoundException());

        try {
            $this->middleware()->handle(
                $this->request('alice', 'alice@example.com', '10.0.0.2'),
                fn (Request $request) => 'next',
            );

            $this->fail('Expected an HTTP exception when no local account exists.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function testUserIsCreatedWhenAutoCreateEnabled(): void
    {
        $user = User::factory()->make(['username' => 'alice', 'email' => 'alice@example.com']);
        $session = $this->sessionExpectingRegeneration();

        $this->auth->shouldReceive('guard')->twice()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->guard->shouldReceive('login')->once()->with($user);
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['email' => 'alice@example.com'])
            ->andThrow(new RecordNotFoundException());
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['username' => 'alice'])
            ->andThrow(new RecordNotFoundException());
        $this->creationService->shouldReceive('handle')
            ->once()
            ->with([
                'username' => 'alice',
                'email' => 'alice@example.com',
                'name_first' => 'alice',
                'name_last' => 'User',
            ])
            ->andReturn($user);

        config(['auth.header.auto_create' => true]);

        $response = $this->middleware()->handle(
            $this->request('alice', 'alice@example.com', '10.0.0.2', $session),
            fn (Request $request) => 'next',
        );

        $this->assertSame('next', $response);
    }

    public function testUsernameEmailCollisionIsRejected(): void
    {
        $existing = User::factory()->make(['username' => 'alice', 'email' => 'other@example.com']);

        $this->auth->shouldReceive('guard')->once()->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturnFalse();
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['email' => 'alice@example.com'])
            ->andThrow(new RecordNotFoundException());
        $this->repository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['username' => 'alice'])
            ->andReturn($existing);

        try {
            $this->middleware()->handle(
                $this->request('alice', 'alice@example.com', '10.0.0.2'),
                fn (Request $request) => 'next',
            );

            $this->fail('Expected a conflict when username maps to a different email.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
    }

    private function middleware(): AuthenticateFromTrustedHeader
    {
        return new AuthenticateFromTrustedHeader(
            $this->auth,
            $this->repository,
            $this->creationService,
        );
    }

    private function request(
        ?string $username = null,
        ?string $email = null,
        string $remoteAddr = '10.0.0.2',
        ?Session $session = null,
    ): Request {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $remoteAddr]);

        if ($username !== null) {
            $request->headers->set('Remote-User', $username);
        }
        if ($email !== null) {
            $request->headers->set('Remote-Email', $email);
        }
        if ($session !== null) {
            $request->setLaravelSession($session);
        }

        return $request;
    }

    private function sessionExpectingRegeneration(): MockInterface
    {
        $session = m::mock(Session::class);
        $session->shouldReceive('remove')->once()->with('auth_confirmation_token');
        $session->shouldReceive('regenerate')->once();

        return $session;
    }
}
