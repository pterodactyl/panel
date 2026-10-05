<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Console\Commands\UpgradeCommandTest;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);

test('upgrade keeps maintenance enabled and reports failure when a deployment step fails', function (string $failed): void {
    $archive = null;
    $permissions = null;
    Process::fake(function (PendingProcess $process) use ($failed, &$archive, &$permissions) {
        $command = $process->command;
        if ($command[0] === 'curl') {
            $archive = $command[4];
            $permissions = fileperms(dirname($archive)) & 0777;
        }

        $step = $command[0] === PHP_BINARY ? $command[2] : $command[0];

        return Process::result(errorOutput: 'Deployment failed', exitCode: $step === $failed ? 1 : 0);
    });

    expect(fn () => Artisan::call('p:upgrade', ['--user' => 'tester', '--group' => 'tester', '--no-interaction' => true]))
        ->toThrow(ProcessFailedException::class);

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'down', '--no-interaction']);
    Process::assertNotRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'up', '--no-interaction']);
    expect(Artisan::output())->not->toContain('Panel has been successfully upgraded.');
    if ($archive !== null) {
        expect($permissions)->toBe(0700);
        $this->assertDirectoryDoesNotExist(dirname($archive));
    }
})->with(['curl', 'tar', 'chmod', 'composer', 'view:clear', 'config:clear', 'migrate', 'chown', 'queue:restart']);

test('upgrade leaves maintenance only after every deployment step succeeds', function (): void {
    Process::fake();

    expect(Artisan::call('p:upgrade', ['--skip-download' => true, '--user' => 'tester', '--group' => 'tester', '--no-interaction' => true]))->toBe(0);

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'migrate', '--no-interaction', '--force', '--seed']);
    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'up', '--no-interaction']);
    Process::assertRanTimes(fn (PendingProcess $process): bool => true, 9);
    expect(Artisan::output())->toContain('Panel has been successfully upgraded.');
});
