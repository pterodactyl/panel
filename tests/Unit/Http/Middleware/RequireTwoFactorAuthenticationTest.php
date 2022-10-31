<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
use Pterodactyl\Exceptions\Http\TwoFactorAuthRequiredException;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

class RequireTwoFactorAuthenticationTest extends MiddlewareTestCase
{
    public function testNoRequirementUserWithout2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        $user = $this->generateRequestUserModel(['use_totp' => false]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testNoRequirementUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        $user = $this->generateRequestUserModel(['use_totp' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testNoRequirementUserWithSecurityKey2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->make(['use_totp' => false])
            ->setRelation('securityKeys', SecurityKey::factory()->count(1)->make());
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isNotEmpty());

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testNoRequirementGuestUser()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        $this->setRequestUserModel();

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/auth/login');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn('auth.login');
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementUserWithout2fa()
    {
        $this->expectException(TwoFactorAuthRequiredException::class);

        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        $user = $this->generateRequestUserModel(['use_totp' => false]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        $user = $this->generateRequestUserModel(['use_totp' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementUserWithSecurityKey2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->make(['use_totp' => false])
            ->setRelation('securityKeys', SecurityKey::factory()->count(1)->make());
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isNotEmpty());

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementGuestUser()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        $this->setRequestUserModel();

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/auth/login');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn('auth.login');
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementUserWithout2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => false]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());
        $this->assertFalse($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementAdminUserWithout2fa()
    {
        $this->expectException(TwoFactorAuthRequiredException::class);

        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => false, 'root_admin' => true]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());
        $this->assertTrue($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());
        $this->assertFalse($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementAdminUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => true, 'root_admin' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isEmpty());
        $this->assertTrue($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementUserWithSecurityKey2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->make(['use_totp' => false])
            ->setRelation('securityKeys', SecurityKey::factory()->count(1)->make());
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertFalse($user->root_admin);
        $this->assertTrue($user->securityKeys->isNotEmpty());
        $this->assertNotEmpty($user->securityKeys);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementAdminUserWithSecurityKey2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->make(['use_totp' => false, 'root_admin' => true])
            ->setRelation('securityKeys', SecurityKey::factory()->count(1)->make());
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->securityKeys->isNotEmpty());
        $this->assertTrue($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementGuestUser()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $this->setRequestUserModel();

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/auth/login');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn('auth.login');
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        /** @var \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication $controller */
        $middleware = $this->app->make(RequireTwoFactorAuthentication::class);
        $middleware->handle($this->request, $this->getClosureAssertions());
    }
}
