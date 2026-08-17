<?php

namespace Pterodactyl\Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Tests\TestCase;
use Illuminate\Testing\PendingCommand;
use Illuminate\Support\Facades\Process;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\ClosureCommand;

class UpgradeCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // The unit suite runs without a database; the pre-flight check needs one.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);

        $this->bringApplicationUp();
    }

    protected function tearDown(): void
    {
        $this->bringApplicationUp();

        parent::tearDown();
    }

    public function testExplicitUserAndGroupAreHandedToTheSecondHalf(): void
    {
        Process::fake();

        $this->upgrade(['--user' => 'nginx', '--group' => 'web'])->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $this->isHandoff($process)
            && in_array('--user=nginx', $process->command, true)
            && in_array('--group=web', $process->command, true));
    }

    public function testUpgradeIsAbandonedWhenAStepExitsNonZero(): void
    {
        Process::fake(['*' => Process::result(exitCode: 1)]);

        $this->upgrade()
            ->expectsOutputToContain('The upgrade did not complete')
            ->assertExitCode(Command::FAILURE);

        Process::assertDidntRun(fn ($process) => $this->isHandoff($process));
    }

    public function testChecksumMismatchStopsBeforeTheApplicationGoesOffline(): void
    {
        Process::fake();

        $this->download(['--checksum' => str_repeat('a', 64)])
            ->expectsOutputToContain('Checksum mismatch')
            ->assertExitCode(Command::FAILURE);

        $this->assertFalse($this->app->isDownForMaintenance());
        Process::assertDidntRun(fn ($process) => $this->isHandoff($process));
    }

    public function testMatchingChecksumAllowsTheUpgradeToProceed(): void
    {
        Process::fake($this->platformRequirementsMet());

        // curl is faked, so the archive stays the empty file tempnam() created.
        $this->download(['--checksum' => hash('sha256', '')])
            ->expectsOutputToContain('Checksum matches.')
            ->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => is_array($process->command) && $process->command[0] === 'tar'
            && in_array('-xzf', $process->command, true));
    }

    public function testMissingChecksumIsReportedButNotFatal(): void
    {
        Process::fake($this->platformRequirementsMet());

        $this->download()
            ->expectsOutputToContain('No --checksum was given')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testReleaseIsRefusedWhenThisMachineCannotRunIt(): void
    {
        Process::fake([
            '*check-platform-reqs*' => Process::result(exitCode: 2, output: 'ext-example missing'),
            '*-xzOf*' => Process::result(output: '{"require":{"php":"^8.2"}}'),
            '*' => Process::result(),
        ]);

        $this->download()
            ->expectsOutputToContain('This release cannot run on this machine')
            ->assertExitCode(Command::FAILURE);

        $this->assertFalse($this->app->isDownForMaintenance());
    }

    public function testPlatformCheckIsSkippedWhenTheArchiveHasNoManifests(): void
    {
        Process::fake(['*-xzOf*' => Process::result(output: ''), '*' => Process::result()]);

        $this->download()
            ->expectsOutputToContain('skipping the platform requirement check')
            ->assertExitCode(Command::SUCCESS);

        // Both the plain and the "./" prefixed candidate are attempted.
        Process::assertRan(fn ($process) => is_array($process->command)
            && in_array('./composer.json', $process->command, true));
    }

    public function testFinalizeRunsTheRemainingStepsAndSetsOwnership(): void
    {
        Process::fake();
        $this->stubRemainingSteps();

        $this->finalize(['--user' => 'nginx', '--group' => 'web'])
            ->expectsOutputToContain('Panel has been successfully upgraded')
            ->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $process->command === ['chown', '-R', 'nginx:web', '.']);
    }

    public function testFinalizeDefaultsOwnershipToTheWebserverUser(): void
    {
        Process::fake();
        $this->stubRemainingSteps();

        $this->finalize()->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $process->command === ['chown', '-R', 'www-data:www-data', '.']);
    }

    public function testFinalizeSurvivesAFailingChown(): void
    {
        Process::fake(['*chown*' => Process::result(exitCode: 1), '*' => Process::result()]);
        $this->stubRemainingSteps();

        $this->finalize()
            ->expectsOutputToContain('Could not set file ownership')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testFinalizeLeavesMaintenanceModeOnWhenMigrationsFail(): void
    {
        Process::fake();
        $this->stubRemainingSteps();
        $this->stubArtisan('migrate {--force} {--seed}', fn () => throw new \RuntimeException('migration blew up'));

        $this->finalize()
            ->expectsOutputToContain('migration blew up')
            ->expectsOutputToContain('left in maintenance mode')
            ->assertExitCode(Command::FAILURE);
    }

    public function testUpgradeStopsWhenTheDatabaseIsUnreachable(): void
    {
        config([
            'database.default' => 'unreachable',
            'database.connections.unreachable' => [
                'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1,
                'database' => 'testing', 'username' => 'testing', 'password' => 'testing',
            ],
        ]);
        Process::fake();

        $this->upgrade()
            ->expectsOutputToContain('Could not connect to the database')
            ->expectsOutputToContain('still online')
            ->assertExitCode(Command::FAILURE);

        Process::assertNothingRan();
    }

    public function testUpgradeStopsWhenARequiredBinaryIsMissing(): void
    {
        Process::fake();
        $path = getenv('PATH');
        putenv('PATH=');
        $_SERVER['PATH'] = '';

        try {
            $this->upgrade()
                ->expectsOutputToContain('could not be found in your PATH')
                ->assertExitCode(Command::FAILURE);
        } finally {
            putenv('PATH=' . $path);
            $_SERVER['PATH'] = $path;
        }

        Process::assertNothingRan();
    }

    public function testUpgradeStopsWhenTheInstallationIsNotWritable(): void
    {
        $directory = sys_get_temp_dir() . '/pterodactyl-readonly-' . getmypid();
        @mkdir($directory);
        @chmod($directory, 0o500);

        if (is_writable($directory)) {
            @rmdir($directory);
            $this->markTestSkipped('This filesystem does not honour a read-only directory.');
        }

        Process::fake();
        $base = $this->app->basePath();
        $this->app->setBasePath($directory);

        try {
            $this->upgrade()
                ->expectsOutputToContain('is not writable by the current user')
                ->assertExitCode(Command::FAILURE);
        } finally {
            $this->app->setBasePath($base);
            @chmod($directory, 0o700);
            @rmdir($directory);
        }
    }

    public function testDetectedOwnershipCanBeCorrectedInteractively(): void
    {
        Process::fake();

        $this->artisan('p:upgrade', ['--skip-download' => true])
            ->expectsConfirmation("Your webserver user has been detected as <fg=blue>[{$this->detected('posix_getpwuid')}]:</> is this correct?", 'no')
            ->expectsQuestion('Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".', 'nginx')
            ->expectsConfirmation("Your webserver group has been detected as <fg=blue>[{$this->detected('posix_getgrgid')}]:</> is this correct?", 'no')
            ->expectsQuestion('Please enter the name of the group running your webserver process. Normally this is the same as your user.', 'web')
            ->expectsConfirmation('Are you sure you want to run the upgrade process for your Panel?', 'yes')
            ->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => $this->isHandoff($process)
            && in_array('--user=nginx', $process->command, true)
            && in_array('--group=web', $process->command, true));
    }

    public function testDeclinedFinalConfirmationChangesNothing(): void
    {
        Process::fake();

        $this->artisan('p:upgrade', ['--skip-download' => true, '--user' => 'nginx', '--group' => 'web'])
            ->expectsConfirmation('Are you sure you want to run the upgrade process for your Panel?', 'no')
            ->expectsOutputToContain('terminated by user')
            ->assertExitCode(Command::SUCCESS);

        Process::assertNothingRan();
    }

    public function testDecliningTheDownloadStillUpgradesFromWhatIsOnDisk(): void
    {
        Process::fake();

        $this->artisan('p:upgrade', ['--user' => 'nginx', '--group' => 'web'])
            ->expectsConfirmation('Would you like to download and unpack the archive files for the latest version?', 'no')
            ->expectsConfirmation('Are you sure you want to run the upgrade process for your Panel?', 'yes')
            ->assertExitCode(Command::SUCCESS);

        Process::assertDidntRun(fn ($process) => is_array($process->command) && $process->command[0] === 'curl');
        Process::assertRan(fn ($process) => $this->isHandoff($process));
    }

    public function testProductionInstallsWithoutDevelopmentDependencies(): void
    {
        config(['app.env' => 'production', 'app.debug' => false]);
        Process::fake();

        $this->upgrade()->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => is_array($process->command) && $process->command[0] === 'composer'
            && in_array('--no-dev', $process->command, true)
            && in_array('--optimize-autoloader', $process->command, true));
    }

    public function testAnExplicitUrlIsUsedVerbatim(): void
    {
        Process::fake($this->platformRequirementsMet());

        $this->download(['--url' => 'https://example.com/panel.tar.gz'])->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => is_array($process->command)
            && in_array('https://example.com/panel.tar.gz', $process->command, true));
    }

    public function testAnExplicitReleaseIsResolvedToATaggedArchive(): void
    {
        Process::fake($this->platformRequirementsMet());

        $this->download(['--release' => '1.11.3'])->assertExitCode(Command::SUCCESS);

        Process::assertRan(fn ($process) => is_array($process->command) && in_array(
            'https://github.com/pterodactyl/panel/releases/download/v1.11.3/panel.tar.gz',
            $process->command,
            true
        ));
    }

    /**
     * Mirrors the owner the command detects, which differs between platforms.
     */
    private function detected(string $lookup): string
    {
        if (!function_exists($lookup)) {
            return 'www-data';
        }

        $id = $lookup === 'posix_getpwuid' ? fileowner('public') : filegroup('public');

        return $lookup($id)['name'] ?? 'www-data';
    }

    private function isHandoff(object $process): bool
    {
        return is_array($process->command) && in_array('--finalize', $process->command, true);
    }

    /**
     * Fakes an archive whose manifests read cleanly and satisfy Composer.
     */
    private function platformRequirementsMet(): array
    {
        return ['*-xzOf*' => Process::result(output: '{"require":{"php":"^8.2"}}'), '*' => Process::result()];
    }

    /**
     * Replaces the Artisan commands the second half calls, so the tests do not
     * need a migrated database.
     */
    private function stubRemainingSteps(): void
    {
        $this->stubArtisan('view:clear');
        $this->stubArtisan('config:clear');
        $this->stubArtisan('migrate {--force} {--seed}');
        $this->stubArtisan('queue:restart');
        $this->stubArtisan('up');
    }

    private function stubArtisan(string $signature, ?\Closure $callback = null): void
    {
        $this->app[Kernel::class]->registerCommand(new ClosureCommand($signature, $callback ?? fn () => 0));
    }

    /**
     * Tests that reach the second half really do go into maintenance mode, and the
     * process that would lift it back out is faked away.
     */
    private function bringApplicationUp(): void
    {
        @unlink(storage_path('framework/down'));
        @unlink(storage_path('framework/maintenance.php'));
    }

    private function upgrade(array $options = []): PendingCommand
    {
        return $this->artisan('p:upgrade', array_merge(['--skip-download' => true, '--no-interaction' => true], $options));
    }

    private function download(array $options = []): PendingCommand
    {
        return $this->artisan('p:upgrade', array_merge(['--no-interaction' => true], $options));
    }

    private function finalize(array $options = []): PendingCommand
    {
        return $this->artisan('p:upgrade', array_merge(['--finalize' => true, '--no-interaction' => true], $options));
    }
}
