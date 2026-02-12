<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\AuthenticateHeader;

class AuthenticateHeaderTest extends MiddlewareTestCase
{
    use RefreshDatabase;

    protected AuthenticateHeader $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthenticateHeader();
    }

    public function test_header_authentication_is_disabled_by_default()
    {
        Config::set('auth.header.enabled', false);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'testuser',
            'X-Auth-Email' => 'test@example.com',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertFalse(Auth::check());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_it_authenticates_user_with_valid_headers()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');

        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'testuser',
            'X-Auth-Email' => 'test@example.com',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_it_finds_user_by_username()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');

        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'testuser',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_it_finds_user_by_email()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');

        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = $this->requestWithHeaders([
            'X-Auth-Email' => 'test@example.com',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_it_auto_creates_user_when_enabled()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');
        Config::set('auth.header.auto_create_user', true);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'newuser',
            'X-Auth-Email' => 'newuser@example.com',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertTrue(Auth::check());
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_it_does_not_auto_create_user_when_disabled()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');
        Config::set('auth.header.auto_create_user', false);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'newuser',
            'X-Auth-Email' => 'newuser@example.com',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertFalse(Auth::check());
        $this->assertDatabaseMissing('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_it_skips_already_authenticated_user()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');

        $user = User::factory()->create();
        Auth::login($user);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'otheruser',
            'X-Auth-Email' => 'other@example.com',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_it_requires_both_headers_for_auto_creation()
    {
        Config::set('auth.header.enabled', true);
        Config::set('auth.header.username_header', 'X-Auth-Username');
        Config::set('auth.header.email_header', 'X-Auth-Email');
        Config::set('auth.header.auto_create_user', true);

        $request = $this->requestWithHeaders([
            'X-Auth-Username' => 'newuser',
        ]);

        $response = $this->middleware->handle($request, fn () => response('ok'));

        $this->assertFalse(Auth::check());
        $this->assertDatabaseMissing('users', [
            'username' => 'newuser',
        ]);
    }

    protected function requestWithHeaders(array $headers): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('/test', 'GET', [], [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function transformHeadersToServerVars(array $headers): array
    {
        $server = [];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        return $server;
    }
}