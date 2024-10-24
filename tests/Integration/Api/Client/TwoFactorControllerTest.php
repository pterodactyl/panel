<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Carbon\Carbon;
use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use PragmaRX\Google2FA\Google2FA;
use Pterodactyl\Models\RecoveryToken;
use PHPUnit\Framework\ExpectationFailedException;

class TwoFactorControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that image data for enabling 2FA is returned by the endpoint and that the user
     * record in the database is updated as expected.
     */
    public function testTwoFactorImageDataIsReturned()
    {
        /** @var User $user */
        $user = User::factory()->create(['use_totp' => false]);

        $this->assertFalse($user->use_totp);
        $this->assertEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);

        $response = $this->actingAs($user)->getJson('/api/client/account/two-factor');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['image_url_data']]);

        $user = $user->refresh();

        $this->assertFalse($user->use_totp);
        $this->assertNotEmpty($user->totp_secret);
        $this->assertEmpty($user->totp_authenticated_at);
    }

    /**
     * Test that an error is returned if the user's account already has 2FA enabled on it.
     */
    public function testErrorIsReturnedWhenTwoFactorIsAlreadyEnabled()
    {
        /** @var User $user */
        $user = User::factory()->create(['use_totp' => true]);

        $response = $this->actingAs($user)->getJson('/api/client/account/two-factor');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'BadRequestHttpException');
        $response->assertJsonPath('errors.0.detail', 'Two-factor authentication is already enabled on this account.');
    }

    /**
     * Test that a validation error is thrown if invalid data is passed to the 2FA endpoint.
     */
    public function testValidationErrorIsReturnedIfInvalidDataIsPassedToEnabled2FA()
    {
        /** @var User $user */
        $user = User::factory()->create(['use_totp' => false]);

        $this->actingAs($user)
            ->postJson('/api/client/account/two-factor', ['code' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.meta.rule', 'required')
            ->assertJsonPath('errors.0.meta.source_field', 'code')
            ->assertJsonPath('errors.1.meta.rule', 'required')
            ->assertJsonPath('errors.1.meta.source_field', 'password');
    }

    /**
     * Tests that 2FA can be enabled on an account for the user.
     */
    public function testTwoFactorCanBeEnabledOnAccount()
    {
        /** @var User $user */
        $user = User::factory()->create(['use_totp' => false]);

        // Make the initial call to get the account setup for 2FA.
        $this->actingAs($user)->getJson('/api/client/account/two-factor')->assertOk();

        $user = $user->refresh();
        $this->assertNotNull($user->totp_secret);

        /** @var Google2FA $service */
        $service = $this->app->make(Google2FA::class);

        $secret = decrypt($user->totp_secret);
        $token = $service->getCurrentOtp($secret);

        $response = $this->actingAs($user)->postJson('/api/client/account/two-factor', [
            'code' => $token,
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('object', 'recovery_tokens');

        $user = $user->refresh();
        $this->assertTrue($user->use_totp);

        $tokens = RecoveryToken::query()->where('user_id', $user->id)->get();
        $this->assertCount(10, $tokens);
        $this->assertStringStartsWith('$2y$10$', $tokens[0]->token);
        // Ensure the recovery tokens that were created include a "created_at" timestamp
        // value on them.
        //
        // @see https://github.com/pterodactyl/panel/issues/3163
        $this->assertNotNull($tokens[0]->created_at);

        $tokens = $tokens->pluck('token')->toArray();

        foreach ($response->json('attributes.tokens') as $raw) {
            foreach ($tokens as $hashed) {
                if (password_verify($raw, $hashed)) {
                    continue 2;
                }
            }

            throw new ExpectationFailedException(sprintf('Failed asserting that token [%s] exists as a hashed value in recovery_tokens table.', $raw));
        }
    }

    /**
     * Test that two-factor authentication can be disabled on an account as long as the password
     * provided is valid for the account.
     */
    public function testTwoFactorCanBeDisabledOnAccount()
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create(['use_totp' => true]);

        $response = $this->actingAs($user)->postJson('/api/client/account/two-factor/disable', [
            'password' => 'invalid',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'BadRequestHttpException');
        $response->assertJsonPath('errors.0.detail', 'The password provided was not valid.');

        $response = $this->actingAs($user)->postJson('/api/client/account/two-factor/disable', [
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $user = $user->refresh();
        $this->assertFalse($user->use_totp);
        $this->assertNotNull($user->totp_authenticated_at);
        $this->assertSame(Carbon::now()->toAtomString(), $user->totp_authenticated_at->toAtomString());
    }

    /**
     * Test that no error is returned when trying to disabled two factor on an account where it
     * was not enabled in the first place.
     */
    public function testNoErrorIsReturnedIfTwoFactorIsNotEnabled()
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create(['use_totp' => false]);

        $response = $this->actingAs($user)->postJson('/api/client/account/two-factor/disable', [
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    /**
     * Test that a valid account password is required when enabling two-factor.
     */
    public function testEnablingTwoFactorRequiresValidPassword()
    {
        $user = User::factory()->create(['use_totp' => false]);

        $this->actingAs($user)
            ->postJson('/api/client/account/two-factor', [
                'code' => '123456',
                'password' => 'foo',
            ])
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.detail', 'The password provided was not valid.');

        $this->assertFalse($user->refresh()->use_totp);
    }

    /**
     * Test that a valid account password is required when disabling two-factor.
     */
    public function testDisablingTwoFactorRequiresValidPassword()
    {
        $user = User::factory()->create(['use_totp' => true]);

        $this->actingAs($user)
            ->postJson('/api/client/account/two-factor/disable', [
                'password' => 'foo',
            ])
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.detail', 'The password provided was not valid.');

        $this->assertTrue($user->refresh()->use_totp);
    }
}
