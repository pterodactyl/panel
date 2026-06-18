<?php

namespace Pterodactyl\Tests\Unit\Console\Commands\Environment\Addons;

use Illuminate\Console\Command;
use Pterodactyl\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Illuminate\Support\Facades\Process;

class RunHooksCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('addons'));

        parent::tearDown();
    }

    public function testHooksAreSkippedWhenDisabled(): void
    {
        config(['addons.hooks_enabled' => false]);
        Process::fake();
        $this->makeHook('example');

        $this->runHooks()->assertExitCode(Command::SUCCESS);

        Process::assertNothingRan();
    }

    public function testExecutableHooksRunForEvent(): void
    {
        config(['addons.hooks_enabled' => true]);
        Process::fake();
        $first = $this->makeHook('alpha');
        $second = $this->makeHook('beta');

        $this->runHooks()->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $process->command === [$first]);
        Process::assertRan(fn ($process) => $process->command === [$second]);
    }

    public function testNonExecutableFilesAreIgnored(): void
    {
        config(['addons.hooks_enabled' => true]);
        Process::fake();
        $executable = $this->makeHook('alpha');
        $ignored = $this->makeHook('beta', executable: false);

        $this->runHooks()->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $process->command === [$executable]);
        Process::assertDidntRun(fn ($process) => $process->command === [$ignored]);
    }

    public function testInvalidEventNameIsRejected(): void
    {
        config(['addons.hooks_enabled' => true]);
        Process::fake();

        $this->runHooks('BadEvent')
            ->expectsOutputToContain('Invalid hook event name')
            ->assertExitCode(Command::INVALID);

        Process::assertNothingRan();
    }

    public function testFailingHookDoesNotAbortRemainingHooks(): void
    {
        config(['addons.hooks_enabled' => true]);
        $failing = $this->makeHook('broken');
        $passing = $this->makeHook('healthy');

        Process::fake([
            '*broken*' => Process::result(exitCode: 3),
            '*' => Process::result(),
        ]);

        $this->runHooks()
            ->expectsOutputToContain('exited with an error')
            ->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $process->command === [$failing]);
        Process::assertRan(fn ($process) => $process->command === [$passing]);
    }

    public function testDeclinedConfirmationSkipsHooks(): void
    {
        config(['addons.hooks_enabled' => true]);
        Process::fake();
        $this->makeHook('example');

        $this->artisan('p:environment:addons:run-hooks', ['event' => 'post-install'])
            ->expectsConfirmation('Execute 1 addon hook script(s) for the "post-install" event? They run with the privileges of this process.', 'no')
            ->assertExitCode(Command::SUCCESS);

        Process::assertNothingRan();
    }

    private function runHooks(string $event = 'post-install'): PendingCommand
    {
        return $this->artisan('p:environment:addons:run-hooks', ['event' => $event, '--no-interaction' => true]);
    }

    private function makeHook(string $addon, string $event = 'post-install', bool $executable = true): string
    {
        $path = base_path("addons/{$addon}/hooks/{$event}");

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "#!/usr/bin/env bash\nexit 0\n");

        if ($executable) {
            chmod($path, 0o755);
        }

        return $path;
    }
}
