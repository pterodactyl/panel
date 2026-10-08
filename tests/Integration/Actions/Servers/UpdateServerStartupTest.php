<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Servers\UpdateServerStartupTest;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Actions\Servers\UpdateServerDockerImage;
use Pterodactyl\Contracts\Servers\UpdatesServerDockerImage;
use Pterodactyl\Contracts\Servers\UpdatesServerStartup;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);
test('non admin can modify server variables', function (): void {
    $server = $this->createServerModel();
    try {
        $this->app->make(UpdatesServerStartup::class)->update($server, ['egg_id' => $server->egg_id + 1, 'environment' => ['BUNGEE_VERSION' => '$$', 'SERVER_JARFILE' => 'server.jar']]);
        $this->fail('This assertion should not be called.');
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(ValidationException::class);
        /** @var ValidationException $exception */
        $errors = $exception->validator->errors()->toArray();
        expect($errors)->toHaveCount(1);
        expect($errors)->toHaveKey('environment.BUNGEE_VERSION');
        expect($errors['environment.BUNGEE_VERSION'])->toHaveCount(1);
        expect($errors['environment.BUNGEE_VERSION'][0])->toBe('The Bungeecord Version variable may only contain letters and numbers.');
    }

    ServerVariable::query()->where('variable_id', $server->variables[1]->id)->delete();
    $result = getService()->update($server, ['egg_id' => $server->egg_id + 1, 'startup' => 'random gibberish', 'environment' => ['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'test.jar']]);
    expect($result)->toBeInstanceOf(Server::class);
    expect($result->variables)->toHaveCount(2);
    expect($result->startup)->toBe($server->startup);
    expect($result->variables[0]->server_value)->toBe('1234');
    expect($result->variables[1]->server_value)->toBe('test.jar');
});
test('server is properly modified as admin user', function (): void {
    /** @var Egg $nextEgg */
    $nextEgg = Egg::query()->where('id', '!=', 1)->firstOrFail();
    $server = $this->createServerModel(['egg_id' => 1]);
    $this->assertNotSame($nextEgg->id, $server->egg_id);
    $response = getService()->update($server, ['egg_id' => $nextEgg->id, 'startup' => 'sample startup', 'skip_scripts' => true, 'docker_image' => 'docker/hodor'], User::USER_LEVEL_ADMIN);
    expect($response)->toBeInstanceOf(Server::class);
    expect($response->egg_id)->toBe($nextEgg->id);
    expect($response->startup)->toBe('sample startup');
    expect($response->image)->toBe('docker/hodor');
    expect($response->skip_scripts)->toBeTrue();
    // Make sure we don't revert back to a lurking bug that causes servers to get marked
    // as not installed when you modify the startup...
    expect($response->isInstalled())->toBeTrue();
});
test('environment variables can be updated by admin', function (): void {
    $server = $this->createServerModel();
    $server->loadMissing(['egg', 'variables']);

    $clone = $this->cloneEggAndVariables($server->egg);
    // This makes the BUNGEE_VERSION variable not user editable.
    $clone->variables()->first()->update(['user_editable' => false]);
    $server->fill(['egg_id' => $clone->id])->saveOrFail();
    $server->refresh();
    ServerVariable::query()->updateOrCreate(['server_id' => $server->id, 'variable_id' => $server->variables[0]->id], ['variable_value' => 'EXIST']);
    $response = getService()->update($server, ['environment' => ['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'test.jar']]);
    expect($response->variables)->toHaveCount(2);
    expect($response->variables[0]->server_value)->toBe('EXIST');
    expect($response->variables[1]->server_value)->toBe('test.jar');

    $response = getService()->update($server, ['environment' => ['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'test.jar']], User::USER_LEVEL_ADMIN);
    expect($response->variables)->toHaveCount(2);
    expect($response->variables[0]->server_value)->toBe('1234');
    expect($response->variables[1]->server_value)->toBe('test.jar');
});
test('admin egg change keeps matching variables and falls back to the new egg defaults', function (): void {
    $server = $this->createServerModel(['startup' => 'old startup', 'image' => 'old/image']);
    $server->loadMissing('egg');

    $nextEgg = $this->cloneEggAndVariables($server->egg);
    $nextEgg->forceFill(['startup' => 'new egg startup', 'docker_images' => ['Default' => 'new/image']])->save();
    $previous = $server->variables->pluck('id', 'env_variable');
    ServerVariable::query()->updateOrCreate(['server_id' => $server->id, 'variable_id' => $previous['BUNGEE_VERSION']], ['variable_value' => '1234']);
    ServerVariable::query()->updateOrCreate(['server_id' => $server->id, 'variable_id' => $previous['SERVER_JARFILE']], ['variable_value' => 'old.jar']);
    $response = getService()->update($server, ['egg_id' => $nextEgg->id, 'environment' => ['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'new.jar']], User::USER_LEVEL_ADMIN);
    expect($response->egg_id)->toBe($nextEgg->id);
    expect($response->startup)->toBe('new egg startup');
    expect($response->image)->toBe('new/image');
    expect($response->variables->pluck('server_value', 'env_variable')->all())->toEqual(['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'new.jar']);
    // Values stored against the previous egg's variables do not linger.
    expect(ServerVariable::query()->where('server_id', $server->id)->whereIn('variable_id', $previous->values())->count())->toBe(0);
    expect(ServerVariable::query()->where('server_id', $server->id)->count())->toBe(2);
});
test('non admin cannot move a server to another egg', function (): void {
    $server = $this->createServerModel();
    $server->loadMissing('egg');

    $nextEgg = $this->cloneEggAndVariables($server->egg);
    $response = getService()->update($server, ['egg_id' => $nextEgg->id, 'environment' => ['BUNGEE_VERSION' => '1234', 'SERVER_JARFILE' => 'test.jar']]);
    expect($response->egg_id)->toBe($server->egg_id);
    expect($response->startup)->toBe($server->startup);
});
test('admin image changes go through the docker image contract only when the image changes', function (): void {
    $spy = new class(new UpdateServerDockerImage()) implements UpdatesServerDockerImage
    {
        /** @var list<string> */
        public array $images = [];

        public function __construct(private readonly UpdatesServerDockerImage $inner) {}

        public function update(Server $server, string $image): Server
        {
            $this->images[] = $image;

            return $this->inner->update($server, $image);
        }
    };
    $this->app->instance(UpdatesServerDockerImage::class, $spy);
    $server = $this->createServerModel(['image' => 'old/image']);

    // Users never change the image, and an unchanged image is not written again.
    getService()->update($server, ['docker_image' => 'user/image']);
    getService()->update($server, ['startup' => 'same image', 'docker_image' => 'old/image'], User::USER_LEVEL_ADMIN);

    expect($spy->images)->toBe([]);

    $response = getService()->update($server, ['startup' => 'new startup', 'docker_image' => 'new/image'], User::USER_LEVEL_ADMIN);

    expect($spy->images)->toBe(['new/image'])
        ->and($response->image)->toBe('new/image')
        ->and($response->startup)->toBe('new startup');
});
test('invalid egg id triggers exception', function (): void {
    $server = $this->createServerModel();
    $this->expectException(ModelNotFoundException::class);
    getService()->update($server, ['egg_id' => 123456789], User::USER_LEVEL_ADMIN);
});
function getService(): UpdatesServerStartup
{
    return (fn () => $this->app->make(UpdatesServerStartup::class))->call(pterodactylTestCase());
}
