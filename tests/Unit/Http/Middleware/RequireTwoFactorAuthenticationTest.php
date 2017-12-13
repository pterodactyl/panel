<?php

namespace Tests\Unit\Http\Middleware;

use Mockery as m;
use Pterodactyl\Models\User;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

class RequireTwoFactorAuthenticationTest extends MiddlewareTestCase
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag|\Mockery\Mock
     */
    private $alert;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
    }

    /**
     * Test that a missing user does not trigger this middleware.
     */
    public function testRequestMissingUser()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that the middleware is ignored on specific routes.
     *
     * @dataProvider ignoredRoutesDataProvider
     */
    public function testRequestOnIgnoredRoute($route)
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn(true);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn($route);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test disabled 2FA requirement.
     */
    public function testTwoFactorRequirementDisabled()
    {
        $this->request->shouldReceive('user')->withNoArgs()->once()->andReturn(true);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test 2FA required for admins as an administrative user who has 2FA disabled.
     */
    public function testTwoFactorEnabledForAdminsAsAdminUserWith2FADisabled()
    {
        $user = factory(User::class)->make(['root_admin' => 1, 'use_totp' => 0]);

        $this->request->shouldReceive('user')->withNoArgs()->times(3)->andReturn($user);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');

        $this->alert->shouldReceive('danger')->with(trans('auth.2fa_must_be_enabled'))->once()->andReturnSelf();
        $this->alert->shouldReceive('flash')->withNoArgs()->once()->andReturnSelf();

        $response = $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('account.security'), $response->getTargetUrl());
    }

    /**
     * Test 2FA required for admins as an administrative user who has 2FA enabled.
     */
    public function testTwoFactorEnabledForAdminsAsAdminUserWith2FAEnabled()
    {
        $user = factory(User::class)->make(['root_admin' => 1, 'use_totp' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->times(3)->andReturn($user);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test 2FA required for admins as an administrative user.
     */
    public function testTwoFactorEnabledForAdminsAsNonAdmin()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);

        $this->request->shouldReceive('user')->withNoArgs()->twice()->andReturn($user);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test 2FA required for all users without 2FA enabled.
     */
    public function testTwoFactorEnabledForAllUsersAsUserWith2FADisabled()
    {
        $user = factory(User::class)->make(['use_totp' => 0]);

        $this->request->shouldReceive('user')->withNoArgs()->twice()->andReturn($user);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');

        $this->alert->shouldReceive('danger')->with(trans('auth.2fa_must_be_enabled'))->once()->andReturnSelf();
        $this->alert->shouldReceive('flash')->withNoArgs()->once()->andReturnSelf();

        $response = $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('account.security'), $response->getTargetUrl());
    }

    /**
     * Test 2FA required for all users without 2FA enabled.
     */
    public function testTwoFactorEnabledForAllUsersAsUserWith2FAEnabled()
    {
        $user = factory(User::class)->make(['use_totp' => 1]);

        $this->request->shouldReceive('user')->withNoArgs()->twice()->andReturn($user);
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Routes that should be ignored.
     *
     * @return array
     */
    public function ignoredRoutesDataProvider()
    {
        return [
            ['account.security'],
            ['account.security.revoke'],
            ['account.security.totp'],
            ['account.security.totp.set'],
            ['account.security.totp.disable'],
            ['auth.totp'],
            ['auth.logout'],
        ];
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication
     */
    private function getMiddleware(): RequireTwoFactorAuthentication
    {
        return new RequireTwoFactorAuthentication($this->alert);
    }
}
