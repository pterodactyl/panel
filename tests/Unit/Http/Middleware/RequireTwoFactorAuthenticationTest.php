<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Mockery as m;
use Pterodactyl\Models\User;
use Pterodactyl\Models\SecurityKey;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\Http\TwoFactorAuthRequiredException;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

class RequireTwoFactorAuthenticationTest extends MiddlewareTestCase
{
    private $alerts;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->alerts = m::mock(AlertsMessageBag::class);
    }

    public function testNoRequirementUserWithout2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        $user = $this->generateRequestUserModel(['use_totp' => false]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testNoRequirementUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        $user = $this->generateRequestUserModel(['use_totp' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testNoRequirementUserWithWebauthn2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->has(SecurityKey::factory()->count(1))
            ->create(['use_totp' => false]);
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertNotEmpty($user->webauthnKeys);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testNoRequirementGuestUser()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_NONE);

        $this->setRequestUserModel();

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/auth/login');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn('auth.login');
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
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

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        $user = $this->generateRequestUserModel(['use_totp' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementRuserWithWebauthn2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->has(SecurityKey::factory()->count(1))
            ->create(['use_totp' => false]);
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertNotEmpty($user->webauthnKeys);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAllRequirementGuestUser()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ALL);

        $this->setRequestUserModel();

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/auth/login');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn('auth.login');
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementUserWithout2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => false]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertFalse($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
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
        $this->assertTrue($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertFalse($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementAdminUserWithTotp2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $user = $this->generateRequestUserModel(['use_totp' => true, 'root_admin' => true]);

        $this->assertTrue($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->root_admin);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementUserWithWebauthn2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->has(SecurityKey::factory()->count(1))->create(['use_totp' => false]);
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertFalse($user->root_admin);
        $this->assertNotEmpty($user->webauthnKeys);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementAdminUserWithWebauthn2fa()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()
            ->has(SecurityKey::factory()->count(1))
            ->create(['use_totp' => false, 'root_admin' => true]);
        $this->setRequestUserModel($user);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
        $this->assertTrue($user->root_admin);
        $this->assertNotEmpty($user->webauthnKeys);

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn(null);
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    public function testAdminRequirementGuestUser()
    {
        // Disable the 2FA requirement
        config()->set('pterodactyl.auth.2fa_required', RequireTwoFactorAuthentication::LEVEL_ADMIN);

        $this->setRequestUserModel();

        $this->request->shouldReceive('getRequestUri')->withNoArgs()->andReturn('/auth/login');
        $this->request->shouldReceive('route->getName')->withNoArgs()->andReturn('auth.login');
        $this->request->shouldReceive('isJson')->withNoArgs()->andReturn(true);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    private function getMiddleware(): RequireTwoFactorAuthentication
    {
        return new RequireTwoFactorAuthentication($this->alerts);
    }
}
