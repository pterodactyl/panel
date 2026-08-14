<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Http\Middleware\HeaderAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HeaderAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        config()->set('auth.header.enabled', true);
    }

    public function test_middleware_does_nothing_when_disabled()
    {
        config()->set('auth.header.enabled', false);

        $middleware = new HeaderAuthentication();
        $request = new Request();

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertFalse(Auth::check());
    }

    public function test_middleware_authenticates_existing_user()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'name_first' => 'Test',
            'name_last' => 'User',
            'external_id' => '',
        ]);

        $middleware = new HeaderAuthentication();
        $request = new Request();
        $request->headers->set('X-Auth-Username', 'testuser');
        $request->headers->set('X-Auth-Email', 'test@example.com');

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_middleware_creates_new_user_when_enabled()
    {
        config()->set('auth.header.auto_create', true);

        $middleware = new HeaderAuthentication();
        $request = new Request();
        $request->headers->set('X-Auth-Username', 'testuser');
        $request->headers->set('X-Auth-Email', 'test@example.com');

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('', $user->external_id);
        $this->assertNotNull($user->uuid);
    }

    public function test_middleware_does_not_create_user_when_auto_create_disabled()
    {
        config()->set('auth.header.auto_create', false);

        $middleware = new HeaderAuthentication();
        $request = new Request();
        $request->headers->set('X-Auth-Username', 'testuser');
        $request->headers->set('X-Auth-Email', 'test@example.com');

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertFalse(Auth::check());
    }

    public function test_middleware_uses_custom_header_names()
    {
        config()->set('auth.header.username_header', 'HTTP_X_USERNAME');
        config()->set('auth.header.email_header', 'HTTP_X_EMAIL');

        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'name_first' => 'Test',
            'name_last' => 'User',
            'external_id' => '',
        ]);

        $middleware = new HeaderAuthentication();
        $request = new Request();
        $request->headers->set('HTTP_X_USERNAME', 'testuser');
        $request->headers->set('HTTP_X_EMAIL', 'test@example.com');

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }
} 