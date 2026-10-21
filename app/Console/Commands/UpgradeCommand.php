<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process as SymfonyProcess;

class UpgradeCommand extends Command
{
    protected const DEFAULT_URL = 'https://github.com/pterodactyl/panel/releases/%s/panel.tar.gz';

    /**
     * Rough lower bound for unpacking an archive and reinstalling dependencies.
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

    private ?ProgressBar $bar = null;

    /**
     * Splits the upgrade over two processes: composer install replaces every class
     * under vendor/ while this one is running, and PHP cannot unload what it has
     * already loaded.
     */
    public function handle(): int
    {
        return $this->option('finalize') ? $this->finalize() : $this->upgrade();
    }

    /**
     * Runs from the installed code and stops once the new code is on disk.
     */
    protected function upgrade(): int
    {
        $skipDownload = !$this->wantsDownload();
        [$user, $group] = $this->resolveOwnership();

        if (!$this->confirmUpgrade()) {
            $this->warn('Upgrade process terminated by user.');

            return self::SUCCESS;
        }

        try {
            $this->preflight($skipDownload);
        } catch (\Exception $exception) {
            return $this->abortWhileOnline($exception);
        }

        $archive = $skipDownload ? null : tempnam(sys_get_temp_dir(), 'pterodactyl-panel-');
        $this->startProgress($skipDownload ? 4 : 7);

        try {
            if (!is_null($archive)) {
                $this->downloadArchive($archive);
            }
        } catch (\Exception $exception) {
            $this->discard($archive);

            return $this->abortWhileOnline($exception);
        }

        try {
            $this->replaceInstallation($archive, $user, $group);
        } catch (\Exception $exception) {
            return $this->abortWhileOffline($exception);
        } finally {
            $this->discard($archive);
        }

        return self::SUCCESS;
    }

    /**
     * Runs as a fresh process from the newly installed code, so booting the
     * framework and calling other Artisan commands is safe here.
     */
    protected function finalize(): int
    {
        $user = $this->option('user') ?: 'www-data';
        $group = $this->option('group') ?: 'www-data';

        $this->startProgress(6);

        try {
            $this->withProgress(fn () => $this->call('view:clear'));
            $this->withProgress(fn () => $this->call('config:clear'));
            $this->withProgress(fn () => $this->call('migrate', ['--force' => true, '--seed' => true]));
            $this->withProgress(fn () => $this->setOwnership($user, $group));
            $this->withProgress(fn () => $this->call('queue:restart'));
            $this->withProgress(fn () => $this->call('up'));
        } catch (\Exception $exception) {
            return $this->abortWhileOffline($exception);
        }

        $this->newLine(2);
        $this->info('Panel has been successfully upgraded. Please ensure you also update any Wings instances: https://pterodactyl.io/wings/1.0/upgrading.html');

        return self::SUCCESS;
    }

    /**
     * Fetches and vets the archive while the Panel is still serving traffic.
     */
    protected function downloadArchive(string $archive): void
    {
        // A temporary file rather than curl piped into tar, so a truncated
        // transfer cannot leave a half-written Panel behind.
        $this->withProgress(fn () => $this->runProcess(['curl', '-L', '--fail', '-o', $archive, $this->getUrl()]));

        $this->withProgress(function () use ($archive) {
            $this->verifyChecksum($archive);
            $this->assertPlatformRequirementsMet($archive);
        });
    }

    /**
     * The offline half, where the installation is actually overwritten.
     */
    protected function replaceInstallation(?string $archive, string $user, string $group): void
    {
        $this->withProgress(fn () => $this->call('down'));

        if (!is_null($archive)) {
            $this->withProgress(fn () => $this->runProcess(['tar', '-xzf', $archive]));
        }

        $this->withProgress(fn () => $this->runProcess(['chmod', '-R', '755', 'storage', 'bootstrap/cache']));
        $this->withProgress(fn () => $this->runProcess($this->composerInstallCommand()));

        // Memory and disk stop agreeing here, so the rest runs elsewhere.
        $handoff = [PHP_BINARY, 'artisan', 'p:upgrade', '--finalize', '--no-interaction', '--user=' . $user, '--group=' . $group];
        $this->withProgress(fn () => $this->runProcess($handoff, 900));
    }

    /**
     * Everything that can be established while the Panel is still serving traffic.
     */
    protected function preflight(bool $skipDownload): void
    {
        $finder = new ExecutableFinder();
        foreach ($skipDownload ? ['composer'] : ['curl', 'tar', 'composer'] as $binary) {
            if (is_null($finder->find($binary))) {
                throw new \RuntimeException("Required executable [{$binary}] could not be found in your PATH.");
            }
        }

        $base = $this->getLaravel()->basePath();
        foreach ([$base, $base . '/vendor'] as $path) {
            if (file_exists($path) && !is_writable($path)) {
                throw new \RuntimeException("The upgrade needs to write to [{$path}], but it is not writable by the current user.");
            }
        }

        $free = disk_free_space($base);
        if ($free !== false && $free < self::REQUIRED_DISK_BYTES) {
            $available = sprintf('Only %dMB of free disk space is available at [%s]; the upgrade needs roughly %dMB.', $free / 1024 / 1024, $base, self::REQUIRED_DISK_BYTES / 1024 / 1024);

            throw new \RuntimeException($available);
        }

        try {
            DB::connection()->getPdo();
        } catch (\Exception $exception) {
            throw new \RuntimeException('Could not connect to the database, so the migration step would fail: ' . $exception->getMessage());
        }
    }

    /**
     * Verifies the archive against a hash the operator supplied. Only one passed on
     * the command line is accepted, since a hash fetched over the same channel as
     * the archive would prove nothing about it.
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
     * Checks this machine against the manifests inside the archive, so an unsupported
     * PHP version or a missing extension surfaces while the Panel is still up.
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
     * Pulls a single file out of the archive without unpacking the rest of it.
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
     * Resolves the eventual file owner. An explicit option wins; detection is the
     * fallback, and the guess is only confirmed when somebody is there to answer.
     */
    protected function resolveOwnership(): array
    {
        $user = $this->option('user');
        if (is_null($user)) {
            $user = posix_getpwuid(fileowner('public'))['name'] ?? 'www-data';

            if ($this->input->isInteractive() && !$this->confirm("Your webserver user has been detected as <fg=blue>[{$user}]:</> is this correct?", true)) {
                $user = $this->anticipate(
                    'Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".',
                    ['www-data', 'nginx', 'apache']
                );
            }
        }

        $group = $this->option('group');
        if (is_null($group)) {
            $group = posix_getgrgid(filegroup('public'))['name'] ?? 'www-data';

            if ($this->input->isInteractive() && !$this->confirm("Your webserver group has been detected as <fg=blue>[{$group}]:</> is this correct?", true)) {
                $group = $this->anticipate(
                    'Please enter the name of the group running your webserver process. Normally this is the same as your user.',
                    ['www-data', 'nginx', 'apache']
                );
            }
        }

        return [$user, $group];
    }

    /**
     * Ownership failures are recoverable by hand, so they do not strand a Panel
     * that is otherwise fully upgraded.
     */
    protected function setOwnership(string $user, string $group): void
    {
        try {
            // "." rather than "*", which skips dotfiles such as .env.
            $this->runProcess(['chown', '-R', "{$user}:{$group}", '.']);
        } catch (\Exception $exception) {
            $this->warn('Could not set file ownership: ' . $exception->getMessage());
            $this->warn("Run \"chown -R {$user}:{$group} .\" from the Panel directory yourself.");
        }
    }

    protected function wantsDownload(): bool
    {
        if ($this->option('skip-download')) {
            return false;
        }

        $this->output->warning('This command does not verify the authenticity of downloaded assets. Please ensure that you trust the download source before continuing. Pass --checksum=<sha256> to have the download checked against a hash you obtained separately. If you do not wish to download an archive, please indicate that using the --skip-download flag, or answering "no" to the question below.');
        $this->output->comment('Download Source (set with --url=):');
        $this->line($this->getUrl());

        return !$this->input->isInteractive() || $this->confirm('Would you like to download and unpack the archive files for the latest version?', true);
    }

    protected function confirmUpgrade(): bool
    {
        return !$this->input->isInteractive() || $this->confirm('Are you sure you want to run the upgrade process for your Panel?');
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
     * Runs an external command and aborts the upgrade if it does not succeed.
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

    protected function startProgress(int $steps): void
    {
        ini_set('output_buffering', '0');

        $this->bar = $this->output->createProgressBar($steps);
        $this->bar->start();
    }

    protected function withProgress(\Closure $callback): void
    {
        $this->bar?->clear();
        $callback();
        $this->bar?->advance();
        $this->bar?->display();
    }

    /**
     * Nothing has been written yet, so the Panel keeps serving traffic.
     */
    protected function abortWhileOnline(\Exception $exception): int
    {
        $this->bar?->clear();
        $this->newLine(2);
        $this->error('The upgrade did not start: ' . $exception->getMessage());
        $this->warn('Nothing has been changed and your Panel is still online.');

        return self::FAILURE;
    }

    /**
     * Maintenance mode is kept on purpose: a half upgraded tree serving traffic is
     * worse than a maintenance page.
     */
    protected function abortWhileOffline(\Exception $exception): int
    {
        $this->bar?->clear();
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

    protected function getUrl(): string
    {
        if ($this->option('url')) {
            return $this->option('url');
        }

        return sprintf(self::DEFAULT_URL, $this->option('release') ? 'download/v' . $this->option('release') : 'latest/download');
    }
}
