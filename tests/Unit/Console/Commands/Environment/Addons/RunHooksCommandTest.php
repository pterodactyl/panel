<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Console\Commands\Environment\Addons\RunHooksCommandTest;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Testing\PendingCommand;
use Pterodactyl\Tests\TestCase;

use function pterodactylTestCase;

uses(TestCase::class);
afterEach(function () {
    File::deleteDirectory(base_path('addons'));
});
test('hooks are skipped when disabled', function () {
    config(['addons.hooks_enabled' => false]);
    Process::fake();
    makeHook('example');
    runHooks()->assertExitCode(Command::SUCCESS);
    Process::assertNothingRan();
});
test('executable hooks run for event', function () {
    config(['addons.hooks_enabled' => true]);
    Process::fake();
    $first = makeHook('alpha');
    $second = makeHook('beta');
    runHooks()->assertExitCode(Command::SUCCESS);
    Process::assertRan(fn ($process) => $process->command === [$first]);
    Process::assertRan(fn ($process) => $process->command === [$second]);
});
test('non executable files are ignored', function () {
    config(['addons.hooks_enabled' => true]);
    Process::fake();
    $executable = makeHook('alpha');
    $ignored = makeHook('beta', executable: false);
    runHooks()->assertExitCode(Command::SUCCESS);
    Process::assertRan(fn ($process) => $process->command === [$executable]);
    Process::assertDidntRun(fn ($process) => $process->command === [$ignored]);
});
test('invalid event name is rejected', function () {
    config(['addons.hooks_enabled' => true]);
    Process::fake();
    runHooks('BadEvent')->expectsOutputToContain('Invalid hook event name')->assertExitCode(Command::INVALID);
    Process::assertNothingRan();
});
test('failing hook does not abort remaining hooks', function () {
    config(['addons.hooks_enabled' => true]);
    $failing = makeHook('broken');
    $passing = makeHook('healthy');
    Process::fake(['*broken*' => Process::result(exitCode: 3), '*' => Process::result()]);
    runHooks()->expectsOutputToContain('exited with an error')->assertExitCode(Command::SUCCESS);
    Process::assertRan(fn ($process) => $process->command === [$failing]);
    Process::assertRan(fn ($process) => $process->command === [$passing]);
});
test('declined confirmation skips hooks', function () {
    config(['addons.hooks_enabled' => true]);
    Process::fake();
    makeHook('example');
    $this->artisan('p:environment:addons:run-hooks', ['event' => 'post-install'])->expectsConfirmation('Execute 1 addon hook script(s) for the "post-install" event? They run with the privileges of this process.', 'no')->assertExitCode(Command::SUCCESS);
    Process::assertNothingRan();
});
function runHooks(string $event = 'post-install'): PendingCommand
{
    return (function () use ($event) {
        return $this->artisan('p:environment:addons:run-hooks', ['event' => $event, '--no-interaction' => true]);
    })->call(pterodactylTestCase());
}
function makeHook(string $addon, string $event = 'post-install', bool $executable = true): string
{
    $path = base_path("addons/{$addon}/hooks/{$event}");
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "#!/usr/bin/env bash\nexit 0\n");
    if ($executable) {
        chmod($path, 0755);
    }

    return $path;
}
