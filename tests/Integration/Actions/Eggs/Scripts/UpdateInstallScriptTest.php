<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Eggs\Scripts\UpdateInstallScriptTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Eggs\UpdatesEggInstallScripts;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;
use Pterodactyl\Models\Egg;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('scripts updated without the http api meet the same rules', function (array $input, string $field): void {
    $egg = Egg::factory()->create(['script_install' => 'echo "original"', 'script_entry' => 'bash', 'script_container' => 'alpine:3.4'])->refresh();
    $before = $egg->getAttributes();

    try {
        $this->app->make(UpdatesEggInstallScripts::class)->update($egg, $input);
        $this->fail('The script was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($egg->fresh()->getAttributes())->toBe($before);
})->with([
    'a script that is not a string' => [['script_install' => ['echo']], 'script_install'],
    'a privileged flag that is not a boolean' => [['script_is_privileged' => 'yes'], 'script_is_privileged'],
    'a null privileged flag' => [['script_is_privileged' => null], 'script_is_privileged'],
    'a blank entrypoint' => [['script_entry' => ''], 'script_entry'],
    'a container that is not a string' => [['script_container' => 5], 'script_container'],
    'a copy source that is not a number' => [['copy_script_from' => 'not-a-number'], 'copy_script_from'],
]);

test('an update changes only the fields it is given', function (): void {
    $source = Egg::factory()->create();
    $egg = Egg::factory()->create(['script_install' => 'echo "original"', 'script_is_privileged' => true, 'script_entry' => 'bash', 'script_container' => 'alpine:3.4', 'copy_script_from' => $source->id]);
    $scripts = $this->app->make(UpdatesEggInstallScripts::class);

    $scripts->update($egg, ['script_entry' => 'ash']);
    $egg->refresh();
    expect($egg->script_entry)->toBe('ash')
        ->and($egg->script_install)->toBe('echo "original"')
        ->and($egg->script_is_privileged)->toBeTrue()
        ->and($egg->script_container)->toBe('alpine:3.4')
        ->and($egg->copy_script_from)->toBe($source->id);

    $scripts->update($egg, ['script_install' => '', 'copy_script_from' => null]);
    $egg->refresh();
    expect($egg->script_install)->toBeNull()
        ->and($egg->copy_script_from)->toBeNull()
        ->and($egg->script_entry)->toBe('ash');
});

test('values are coerced the way laravel validates them', function (int|string $flag, bool $expected): void {
    $source = Egg::factory()->create();
    $egg = Egg::factory()->create(['script_is_privileged' => ! $expected]);

    $this->app->make(UpdatesEggInstallScripts::class)->update($egg, ['script_is_privileged' => $flag, 'copy_script_from' => (string) $source->id]);

    $egg->refresh();
    expect($egg->script_is_privileged)->toBe($expected)
        ->and($egg->copy_script_from)->toBe($source->id);
})->with([[1, true], ['1', true], [0, false], ['0', false]]);

test('a script can only be copied from an egg with a script of its own', function (): void {
    $source = Egg::factory()->create();
    $copy = Egg::factory()->create(['copy_script_from' => $source->id]);
    $egg = Egg::factory()->create(['script_entry' => 'bash']);
    $scripts = $this->app->make(UpdatesEggInstallScripts::class);

    expect(fn () => $scripts->update($egg, ['script_entry' => 'ash', 'copy_script_from' => $copy->id]))->toThrow(InvalidCopyFromException::class)
        ->and(fn () => $scripts->update($egg, ['copy_script_from' => 999999]))->toThrow(InvalidCopyFromException::class)
        ->and($egg->refresh()->script_entry)->toBe('bash')
        ->and($egg->copy_script_from)->toBeNull();
});
