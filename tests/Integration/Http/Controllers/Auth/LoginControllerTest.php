<?php

namespace Pterodactyl\Tests\Integration\Http\Controllers\Auth;

use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

class LoginControllerTest extends HttpTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake([FailedCaptcha::class]);
    }

    /**
     * Test that a login request without a reCAPTCHA response is rejected with a
     * 400 error when reCAPTCHA is enabled, rather than causing a server error.
     */
    public function testLoginWithoutRecaptchaResponseReturnsBadRequest(): void
    {
        config()->set('recaptcha.enabled', true);

        $this->postJson('/auth/login', [
            'user' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertStatus(400)
            ->assertJsonPath('errors.0.detail', 'Failed to validate reCAPTCHA data.');

        Event::assertDispatched(fn (FailedCaptcha $event) => is_null($event->domain));
    }
}
