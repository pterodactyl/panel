<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Servers\VariableValidatorServiceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
beforeEach(function () {
    /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
    $this->egg = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();
});
test('environment variables can be validated', function () {
    $egg = $this->cloneEggAndVariables($this->egg);
    try {
        getService()->handle($egg, ['BUNGEE_VERSION' => '1.2.3']);
        $this->fail('This statement should not be reached.');
    } catch (ValidationException $exception) {
        $errors = $exception->errors();
        expect($errors)->toHaveCount(2);
        expect($errors)->toHaveKey('environment.BUNGEE_VERSION');
        expect($errors)->toHaveKey('environment.SERVER_JARFILE');
        expect($errors['environment.BUNGEE_VERSION'][0])->toBe('The Bungeecord Version variable may only contain letters and numbers.');
        expect($errors['environment.SERVER_JARFILE'][0])->toBe('The Bungeecord Jar File variable field is required.');
    }
    $response = getService()->handle($egg, ['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'server.jar']);
    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toHaveCount(2);
    expect($response->get(0)->key)->toBe('BUNGEE_VERSION');
    expect($response->get(0)->value)->toBe('1234');
    expect($response->get(1)->key)->toBe('SERVER_JARFILE');
    expect($response->get(1)->value)->toBe('server.jar');
});
test('normal user cannot validate non user editable variables', function () {
    $egg = $this->cloneEggAndVariables($this->egg);
    $egg->variables()->first()->update(['user_editable' => false]);
    $response = getService()->handle($egg, [
        // This is an invalid value, but it shouldn't cause any issues since it should be skipped.
        'BUNGEE_VERSION' => '1.2.3',
        'SERVER_JARFILE' => 'server.jar',
    ]);
    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toHaveCount(1);
    expect($response->get(0)->key)->toBe('SERVER_JARFILE');
    expect($response->get(0)->value)->toBe('server.jar');
});
test('environment variables can be updated as admin', function () {
    $egg = $this->cloneEggAndVariables($this->egg);
    $egg->variables()->first()->update(['user_editable' => false]);
    try {
        getService()->setUserLevel(User::USER_LEVEL_ADMIN)->handle($egg, ['BUNGEE_VERSION' => '1.2.3', 'SERVER_JARFILE' => 'server.jar']);
        $this->fail('This statement should not be reached.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveCount(1);
        expect($exception->errors())->toHaveKey('environment.BUNGEE_VERSION');
    }
    $response = getService()->setUserLevel(User::USER_LEVEL_ADMIN)->handle($egg, ['BUNGEE_VERSION' => '123', 'SERVER_JARFILE' => 'server.jar']);
    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toHaveCount(2);
    expect($response->get(0)->key)->toBe('BUNGEE_VERSION');
    expect($response->get(0)->value)->toBe('123');
    expect($response->get(1)->key)->toBe('SERVER_JARFILE');
    expect($response->get(1)->value)->toBe('server.jar');
});
test('nullable environment variables can be used correctly', function () {
    $egg = $this->cloneEggAndVariables($this->egg);
    $egg->variables()->where('env_variable', '!=', 'BUNGEE_VERSION')->delete();
    $egg->variables()->update(['rules' => 'nullable|string']);
    $response = getService()->handle($egg, []);
    expect($response)->toHaveCount(1);
    expect($response->get(0)->value)->toBeNull();
    $response = getService()->handle($egg, ['BUNGEE_VERSION' => null]);
    expect($response)->toHaveCount(1);
    expect($response->get(0)->value)->toBeNull();
    $response = getService()->handle($egg, ['BUNGEE_VERSION' => '']);
    expect($response)->toHaveCount(1);
    expect($response->get(0)->value)->toBe('');
});
function getService(): VariableValidatorService
{
    return (function () {
        return $this->app->make(VariableValidatorService::class);
    })->call(pterodactylTestCase());
}
