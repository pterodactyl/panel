<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process as SymfonyProcess;

class UpgradeCommand extends Command
{
    protected const DEFAULT_URL = 'https://github.com/pterodactyl/panel/releases/%s/panel.tar.gz';

    /**
     * Rough lower bound on the space needed to unpack an archive over the existing
     * tree and reinstall dependencies on top of it.
     */
    protected const REQUIRED_DISK_BYTES = 512 * 1024 * 1024;

    protected $signature = 'p:upgrade
        {--user= : The user that PHP runs under. All files will be owned by this user.}
        {--group= : The group that PHP runs under. All files will be owned by this group.}
        {--url= : The specific archive to download.}
        {--release= : A specific Pterodactyl version to download from GitHub. Leave blank to use latest.}
        {--checksum= : Expected SHA256 hash of the archive. The upgrade is aborted if the download does not match.}
        {--skip-download : If set no archive will be downloaded.}
        {--finalize : Internal. Runs the second half of the upgrade; the command invokes this on itself.}';

    protected $description = 'Downloads a new archive for Pterodactyl from GitHub and then executes the normal upgrade commands.';

    /**
     * An upgrade is split across two processes on purpose.
     *
     * The first half runs from the code that is currently installed and stops the
     * moment the new code is on disk. The second half is a brand new PHP process,
     * so it boots the code that was just installed. This is not a stylistic choice:
     * `composer install` replaces the autoloader and every class under vendor/ while
     * the first process is still running, and PHP cannot unload the classes it has
     * already loaded. Anything the old process touches after that point is a coin
     * flip between the definition it holds in memory and the file now on disk.
     */
    public function handle(): int
    {
        return $this->option('finalize') ? $this->finalize() : $this->stage();
    }

    /**
     * Runs from the currently installed code, and does everything up to and
     * including putting the new code on disk.
     *
     * The ordering matters. Every check that can fail cheaply happens before the
     * Panel is taken offline and before a single file is written, so that an
     * upgrade which was never going to work costs the operator a message rather
     * than an outage.
     */
    protected function stage(): int
    {
        $skipDownload = $this->option('skip-download');

        if (!$skipDownload) {
            $this->output->warning('This command does not verify the authenticity of downloaded assets. Please ensure that you trust the download source before continuing. Pass --checksum=<sha256> to have the download checked against a hash you obtained separately. If you do not wish to download an archive, please indicate that using the --skip-download flag, or answering "no" to the question below.');
            $this->output->comment('Download Source (set with --url=):');
            $this->line($this->getUrl());
        }

        [$user, $group] = $this->resolveOwnership();

        if ($this->input->isInteractive()) {
            if (!$skipDownload) {
                $skipDownload = !$this->confirm('Would you like to download and unpack the archive files for the latest version?', true);
            }

            if (!$this->confirm('Are you sure you want to run the upgrade process for your Panel?')) {
                $this->warn('Upgrade process terminated by user.');

                return self::SUCCESS;
            }
        }

        $this->step('Running pre-flight checks');
        if ($problem = $this->preflight($skipDownload)) {
            $this->error($problem);
            $this->warn('Nothing has been changed and your Panel is still online.');

            return self::FAILURE;
        }

        $archive = null;

        // Fetching and vetting the archive is kept in its own attempt because none
        // of it touches the installation. Anything that goes wrong here is still a
        // clean abort, with the Panel serving traffic exactly as it was.
        try {
            if (!$skipDownload) {
                $archive = tempnam(sys_get_temp_dir(), 'pterodactyl-panel-');

                // Downloading to a temporary file rather than piping curl straight
                // into tar means a truncated or failed transfer cannot leave a
                // half-written Panel behind, and gives us something to hash.
                $this->step('Downloading the release archive');
                $this->runProcess(['curl', '-L', '--fail', '-o', $archive, $this->getUrl()]);

                $this->step('Verifying the release archive');
                $this->verifyChecksum($archive);
                $this->assertPlatformRequirementsMet($archive);
            }
        } catch (\Exception $exception) {
            $this->discard($archive);
            $this->newLine(2);
            $this->error('The upgrade did not start: ' . $exception->getMessage());
            $this->warn('Nothing has been changed and your Panel is still online.');

            return self::FAILURE;
        }

        try {
            // From here on the Panel is offline and files start moving.
            $this->step('Putting the Panel into maintenance mode');
            $this->call('down');

            if (!is_null($archive)) {
                $this->step('Unpacking the release archive');
                $this->runProcess(['tar', '-xzf', $archive]);
            }

            $this->step('Fixing storage permissions');
            $this->runProcess(['chmod', '-R', '755', 'storage', 'bootstrap/cache']);

            $this->step('Installing dependencies');
            $this->runProcess($this->composerInstallCommand());

            // Past this line the classes held in memory no longer match the ones on
            // disk, so the rest of the upgrade is handed to a fresh process.
            $this->step('Handing over to the newly installed code');
            $handoff = [PHP_BINARY, 'artisan', 'p:upgrade', '--finalize', '--no-interaction', '--user=' . $user, '--group=' . $group];
            $this->runProcess($handoff, 900);
        } catch (\Exception $exception) {
            return $this->abort($exception);
        } finally {
            $this->discard($archive);
        }

        return self::SUCCESS;
    }

    /**
     * Runs as a fresh process from the code that was just installed, which is why
     * it is safe to boot the framework and call other Artisan commands here.
     */
    protected function finalize(): int
    {
        $user = $this->option('user') ?: 'www-data';
        $group = $this->option('group') ?: 'www-data';

        try {
            $this->step('Clearing cached views and configuration');
            $this->call('view:clear');
            $this->call('config:clear');

            $this->step('Running database migrations');
            $this->call('migrate', ['--force' => true, '--seed' => true]);

            $this->step("Setting file ownership to {$user}:{$group}");
            try {
                // "." rather than "*" so that dotfiles, .env above all, are included.
                $this->runProcess(['chown', '-R', "{$user}:{$group}", '.']);
            } catch (\Exception $exception) {
                // Wrong ownership is worth shouting about but it is recoverable by
                // hand, and aborting here would strand a Panel that is otherwise
                // fully upgraded in maintenance mode.
                $this->warn('Could not set file ownership: ' . $exception->getMessage());
                $this->warn("Run \"chown -R {$user}:{$group} .\" from the Panel directory yourself.");
            }

            $this->step('Restarting queue workers');
            $this->call('queue:restart');

            $this->step('Taking the Panel out of maintenance mode');
            $this->call('up');
        } catch (\Exception $exception) {
            return $this->abort($exception);
        }

        $this->newLine(2);
        $this->info('Panel has been successfully upgraded. Please ensure you also update any Wings instances: https://pterodactyl.io/wings/1.0/upgrading.html');

        return self::SUCCESS;
    }

    /**
     * Everything that can be established while the Panel is still serving traffic.
     */
    protected function preflight(bool $skipDownload): ?string
    {
        $finder = new ExecutableFinder();
        foreach ($skipDownload ? ['composer'] : ['curl', 'tar', 'composer'] as $binary) {
            if (is_null($finder->find($binary))) {
                return "Required executable [{$binary}] could not be found in your PATH.";
            }
        }

        $base = $this->getLaravel()->basePath();
        foreach ([$base, $base . '/vendor'] as $path) {
            if (file_exists($path) && !is_writable($path)) {
                return "The upgrade needs to write to [{$path}], but it is not writable by the current user.";
            }
        }

        $free = disk_free_space($base);
        if ($free !== false && $free < self::REQUIRED_DISK_BYTES) {
            return sprintf(
                'Only %dMB of free disk space is available at [%s]; the upgrade needs roughly %dMB.',
                $free / 1024 / 1024,
                $base,
                self::REQUIRED_DISK_BYTES / 1024 / 1024
            );
        }

        try {
            DB::connection()->getPdo();
        } catch (\Exception $exception) {
            return 'Could not connect to the database, so the migration step would fail: ' . $exception->getMessage();
        }

        return null;
    }

    /**
     * Compare the downloaded archive against a hash the operator supplied.
     *
     * A hash fetched over the same channel as the archive would prove nothing about
     * its authenticity, so this deliberately only accepts one passed on the command
     * line, from a source the operator trusts.
     */
    protected function verifyChecksum(string $archive): void
    {
        $expected = $this->option('checksum');
        if (is_null($expected)) {
            $this->warn('No --checksum was given, so the archive could not be verified.');

            return;
        }

        $actual = hash_file('sha256', $archive);
        if (!hash_equals(strtolower($expected), $actual)) {
            throw new \RuntimeException("Checksum mismatch: expected [{$expected}] but the download hashes to [{$actual}].");
        }

        $this->line('Checksum matches.');
    }

    /**
     * Ask Composer whether this machine can actually run the release we are about to
     * unpack, using the manifests from inside the archive rather than the ones that
     * are already installed. Catching a PHP or extension mismatch here means finding
     * out while the Panel is still up and the tree is still untouched, rather than
     * halfway through `composer install` with the Panel already offline.
     */
    protected function assertPlatformRequirementsMet(string $archive): void
    {
        $directory = tempnam(sys_get_temp_dir(), 'pterodactyl-reqs-');
        @unlink($directory);
        @mkdir($directory);

        try {
            foreach (['composer.json', 'composer.lock'] as $file) {
                if (!$this->extractFile($archive, $file, $directory . '/' . $file)) {
                    $this->warn("Could not read {$file} from the archive; skipping the platform requirement check.");

                    return;
                }
            }

            $result = Process::path($directory)->run(['composer', 'check-platform-reqs', '--no-interaction']);

            if ($result->failed()) {
                $report = $result->output() . $result->errorOutput();

                throw new \RuntimeException("This release cannot run on this machine:\n" . $report);
            }

            $this->line('Platform requirements satisfied.');
        } finally {
            foreach (['composer.json', 'composer.lock'] as $file) {
                @unlink($directory . '/' . $file);
            }
            @rmdir($directory);
        }
    }

    /**
     * Pull a single file out of the archive without unpacking any of the rest of it.
     */
    protected function extractFile(string $archive, string $file, string $destination): bool
    {
        // Archives have been published both with and without a leading "./".
        foreach ([$file, './' . $file] as $candidate) {
            $result = Process::run(['tar', '-xzOf', $archive, $candidate]);

            if ($result->successful() && $result->output() !== '') {
                file_put_contents($destination, $result->output());

                return true;
            }
        }

        return false;
    }

    /**
     * Work out who should own the files once the upgrade is done.
     *
     * An explicitly passed option always wins. Detection is only a fallback, and the
     * guess is only put to the operator when there is somebody there to answer, so
     * that the flags behave the same whether or not the command is run by hand.
     */
    protected function resolveOwnership(): array
    {
        $user = $this->option('user');
        if (is_null($user)) {
            $user = function_exists('posix_getpwuid')
                ? (posix_getpwuid(fileowner('public'))['name'] ?? 'www-data')
                : 'www-data';

            if ($this->input->isInteractive() && !$this->confirm("Your webserver user has been detected as <fg=blue>[{$user}]:</> is this correct?", true)) {
                $user = $this->anticipate(
                    'Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".',
                    ['www-data', 'nginx', 'apache']
                );
            }
        }

        $group = $this->option('group');
        if (is_null($group)) {
            $group = function_exists('posix_getgrgid')
                ? (posix_getgrgid(filegroup('public'))['name'] ?? 'www-data')
                : 'www-data';

            if ($this->input->isInteractive() && !$this->confirm("Your webserver group has been detected as <fg=blue>[{$group}]:</> is this correct?", true)) {
                $group = $this->anticipate(
                    'Please enter the name of the group running your webserver process. Normally this is the same as your user.',
                    ['www-data', 'nginx', 'apache']
                );
            }
        }

        return [$user, $group];
    }

    protected function composerInstallCommand(): array
    {
        $command = ['composer', 'install', '--no-ansi', '--no-interaction'];

        if (config('app.env') === 'production' && !config('app.debug')) {
            $command[] = '--optimize-autoloader';
            $command[] = '--no-dev';
        }

        return $command;
    }

    /**
     * Run an external command and abort the upgrade if it does not succeed.
     *
     * Every step of an upgrade is load-bearing. A step that fails quietly, which is
     * what happened while the exit status went unchecked, leaves the tree in a state
     * nothing downstream is expecting and still reports success at the end.
     */
    protected function runProcess(array $command, int $timeout = 600): void
    {
        $this->line('$upgrader> ' . implode(' ', $command));

        $stream = fn ($type, $buffer) => $this->{$type === SymfonyProcess::ERR ? 'error' : 'line'}($buffer);

        $result = Process::path($this->getLaravel()->basePath())
            ->timeout($timeout)
            ->run($command, $stream);

        if ($result->failed()) {
            throw new \RuntimeException(sprintf('[%s] exited with status %s.', $command[0], $result->exitCode()));
        }
    }

    /**
     * The Panel is deliberately left in maintenance mode. A half upgraded tree will
     * serve errors or, worse, write bad data; an honest maintenance page is the
     * better of those two outcomes.
     */
    protected function abort(\Exception $exception): int
    {
        $this->newLine(2);
        $this->error('The upgrade did not complete: ' . $exception->getMessage());
        $this->warn('Your Panel has been left in maintenance mode on purpose, because the installation may be half upgraded.');
        $this->warn('Once the problem above is resolved, re-run this command with --skip-download to pick up where it left off, or run "php artisan up" to bring the Panel back online as it is.');

        return self::FAILURE;
    }

    protected function discard(?string $archive): void
    {
        if (!is_null($archive) && file_exists($archive)) {
            @unlink($archive);
        }
    }

    protected function step(string $message): void
    {
        $this->newLine();
        $this->line("<fg=blue>==></> {$message}");
    }

    protected function getUrl(): string
    {
        if ($this->option('url')) {
            return $this->option('url');
        }

        return sprintf(self::DEFAULT_URL, $this->option('release') ? 'download/v' . $this->option('release') : 'latest/download');
    }
}
