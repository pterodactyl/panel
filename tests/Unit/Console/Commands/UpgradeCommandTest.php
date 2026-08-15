<?php

namespace Pterodactyl\Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Tests\TestCase;
use Illuminate\Testing\PendingCommand;
use Illuminate\Support\Facades\Process;

class UpgradeCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // The unit suite runs without a database, but the pre-flight check wants a
        // working connection: a migration that cannot run is the whole reason the
        // command used to strand Panels in maintenance mode.
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

    /**
     * Any test that reaches the second half of the upgrade really does put the
     * application into maintenance mode, and the process that would lift it back
     * out is faked away.
     */
    private function bringApplicationUp(): void
    {
        @unlink(storage_path('framework/down'));
        @unlink(storage_path('framework/maintenance.php'));
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

        // The old command discarded every exit status, so a failed step still ran
        // the migrations behind it and reported a successful upgrade at the end.
        Process::assertDidntRun(fn ($process) => $this->isHandoff($process));
    }

    public function testChecksumMismatchStopsBeforeTheApplicationGoesOffline(): void
    {
        Process::fake();

        $this->artisan('p:upgrade', ['--no-interaction' => true, '--checksum' => str_repeat('a', 64)])
            ->expectsOutputToContain('Checksum mismatch')
            ->assertExitCode(Command::FAILURE);

        $this->assertFalse($this->app->isDownForMaintenance());
        Process::assertDidntRun(fn ($process) => $this->isHandoff($process));
    }

    private function isHandoff(object $process): bool
    {
        return is_array($process->command) && in_array('--finalize', $process->command, true);
    }

    private function upgrade(array $options = []): PendingCommand
    {
        return $this->artisan('p:upgrade', array_merge(['--skip-download' => true, '--no-interaction' => true], $options));
    }
}
