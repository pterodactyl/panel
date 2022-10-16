<?php

namespace Pterodactyl\Tests\Integration\Services\Servers;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Servers\VariableValidatorService;

class VariableValidatorServiceTest extends IntegrationTestCase
{
    protected Egg $egg;

    public function setUp(): void
    {
        parent::setUp();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->egg = Egg::query()
            ->where('author', 'support@pterodactyl.io')
            ->where('name', 'Bungeecord')
            ->firstOrFail();
    }

    /**
     * Test that environment variables for a server are validated as expected.
     */
    public function testEnvironmentVariablesCanBeValidated()
    {
        $egg = $this->cloneEggAndVariables($this->egg);

        try {
            $this->getService()->handle($egg->id, [
                'BUNGEE_VERSION' => '1.2.3',
            ]);

            $this->fail('This statement should not be reached.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            $this->assertCount(2, $errors);
            $this->assertArrayHasKey('environment.BUNGEE_VERSION', $errors);
            $this->assertArrayHasKey('environment.SERVER_JARFILE', $errors);
            $this->assertSame('The Bungeecord Version variable may only contain letters and numbers.', $errors['environment.BUNGEE_VERSION'][0]);
            $this->assertSame('The Bungeecord Jar File variable field is required.', $errors['environment.SERVER_JARFILE'][0]);
        }

        $response = $this->getService()->handle($egg->id, [
            'BUNGEE_VERSION' => '1234',
            'SERVER_JARFILE' => 'server.jar',
        ]);

        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        $this->assertSame('BUNGEE_VERSION', $response->get(0)->key);
        $this->assertSame('1234', $response->get(0)->value);
        $this->assertSame('SERVER_JARFILE', $response->get(1)->key);
        $this->assertSame('server.jar', $response->get(1)->value);
    }

    /**
     * Test that variables that are user_editable=false do not get validated (or returned) by
     * the handler.
     */
    public function testNormalUserCannotValidateNonUserEditableVariables()
    {
        $egg = $this->cloneEggAndVariables($this->egg);
        $egg->variables()->first()->update([
            'user_editable' => false,
        ]);

        $response = $this->getService()->handle($egg->id, [
            // This is an invalid value, but it shouldn't cause any issues since it should be skipped.
            'BUNGEE_VERSION' => '1.2.3',
            'SERVER_JARFILE' => 'server.jar',
        ]);

        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(1, $response);
        $this->assertSame('SERVER_JARFILE', $response->get(0)->key);
        $this->assertSame('server.jar', $response->get(0)->value);
    }

    public function testEnvironmentVariablesCanBeUpdatedAsAdmin()
    {
        $egg = $this->cloneEggAndVariables($this->egg);
        $egg->variables()->first()->update([
            'user_editable' => false,
        ]);

        try {
            $this->getService()->setUserLevel(User::USER_LEVEL_ADMIN)->handle($egg->id, [
                'BUNGEE_VERSION' => '1.2.3',
                'SERVER_JARFILE' => 'server.jar',
            ]);

            $this->fail('This statement should not be reached.');
        } catch (ValidationException $exception) {
            $this->assertCount(1, $exception->errors());
            $this->assertArrayHasKey('environment.BUNGEE_VERSION', $exception->errors());
        }

        $response = $this->getService()->setUserLevel(User::USER_LEVEL_ADMIN)->handle($egg->id, [
            'BUNGEE_VERSION' => '123',
            'SERVER_JARFILE' => 'server.jar',
        ]);

        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(2, $response);
        $this->assertSame('BUNGEE_VERSION', $response->get(0)->key);
        $this->assertSame('123', $response->get(0)->value);
        $this->assertSame('SERVER_JARFILE', $response->get(1)->key);
        $this->assertSame('server.jar', $response->get(1)->value);
    }

    public function testNullableEnvironmentVariablesCanBeUsedCorrectly()
    {
        $egg = $this->cloneEggAndVariables($this->egg);
        $egg->variables()->where('env_variable', '!=', 'BUNGEE_VERSION')->delete();

        $egg->variables()->update(['rules' => 'nullable|string']);

        $response = $this->getService()->handle($egg->id, []);
        $this->assertCount(1, $response);
        $this->assertNull($response->get(0)->value);

        $response = $this->getService()->handle($egg->id, ['BUNGEE_VERSION' => null]);
        $this->assertCount(1, $response);
        $this->assertNull($response->get(0)->value);

        $response = $this->getService()->handle($egg->id, ['BUNGEE_VERSION' => '']);
        $this->assertCount(1, $response);
        $this->assertSame('', $response->get(0)->value);
    }

    private function getService(): VariableValidatorService
    {
        return $this->app->make(VariableValidatorService::class);
    }
}
