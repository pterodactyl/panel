<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands;

use Closure;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Pterodactyl\Services\FilesystemChanges;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\Console\Helper\ProgressBar;

#[Description('Downloads a new archive for Pterodactyl from GitHub and then executes the normal upgrade commands.')]
#[Signature('p:upgrade
        {--user= : The user that PHP runs under. All files will be owned by this user.}
        {--group= : The group that PHP runs under. All files will be owned by this group.}
        {--url= : The specific archive to download.}
        {--release= : A specific Pterodactyl version to download from GitHub. Leave blank to use latest.}
        {--skip-download : If set no archive will be downloaded.}')]
class UpgradeCommand extends Command
{
    protected const string DEFAULT_URL = 'https://github.com/pterodactyl/panel/releases/%s/panel.tar.gz';

    /**
     * Executes an upgrade command which will run through all of our standard
     * commands for Pterodactyl and enable users to basically just download
     * the archive and execute this and be done.
     *
     * This places the application in maintenance mode as well while the commands
     * are being executed.
     *
     * @throws Exception
     */
    public function handle(FilesystemChanges $files): int
    {
        $skipDownload = $this->option('skip-download');
        if (! $skipDownload) {
            $this->output->warning('This command does not verify the integrity of downloaded assets. Please ensure that you trust the download source before continuing. If you do not wish to download an archive, please indicate that using the --skip-download flag, or answering "no" to the question below.');
            $this->output->comment('Download Source (set with --url=):');
            $this->line($this->getUrl());
        }

        $userOption = $this->option('user');
        $groupOption = $this->option('group');
        $user = $userOption === null ? 'www-data' : JsonValueGuard::string($userOption);
        $group = $groupOption === null ? 'www-data' : JsonValueGuard::string($groupOption);
        if ($this->input->isInteractive()) {
            if (! $skipDownload) {
                $skipDownload = ! $this->confirm('Would you like to download and unpack the archive files for the latest version?', true);
            }

            if (($this->option('user')) === null) {
                $ownerId = fileowner('public');
                $userDetails = $ownerId !== false ? posix_getpwuid($ownerId) : false;
                $user = $userDetails !== false ? $userDetails['name'] : 'www-data';

                if (! $this->confirm("Your webserver user has been detected as <fg=blue>[{$user}]:</> is this correct?", true)) {
                    $user = JsonValueGuard::string($this->anticipate(
                        'Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".',
                        [
                            'www-data',
                            'nginx',
                            'apache',
                        ]
                    ));
                }
            }

            if (($this->option('group')) === null) {
                $groupId = filegroup('public');
                $groupDetails = $groupId !== false ? posix_getgrgid($groupId) : false;
                $group = $groupDetails !== false ? $groupDetails['name'] : 'www-data';

                if (! $this->confirm("Your webserver group has been detected as <fg=blue>[{$group}]:</> is this correct?", true)) {
                    $group = JsonValueGuard::string($this->anticipate(
                        'Please enter the name of the group running your webserver process. Normally this is the same as your user.',
                        [
                            'www-data',
                            'nginx',
                            'apache',
                        ]
                    ));
                }
            }

            if (! $this->confirm('Are you sure you want to run the upgrade process for your Panel?')) {
                $this->warn('Upgrade process terminated by user.');

                return self::SUCCESS;
            }
        }

        $bar = $this->output->createProgressBar($skipDownload ? 9 : 10);
        $bar->start();

        $this->withProgress($bar, fn () => $this->runArtisan('down'));

        if (! $skipDownload) {
            $this->withProgress($bar, fn () => $this->download($files));
        }

        $this->withProgress($bar, fn () => $this->runProcess(['chmod', '-R', '755', 'storage', 'bootstrap/cache']));

        $this->withProgress($bar, function (): void {
            $command = ['composer', 'install', '--no-ansi'];
            if ($this->getLaravel()->isProduction() && ! config('app.debug')) {
                $command[] = '--optimize-autoloader';
                $command[] = '--no-dev';
            }

            $this->runProcess($command);
        });

        $this->withProgress($bar, fn () => $this->runArtisan('view:clear'));
        $this->withProgress($bar, fn () => $this->runArtisan('config:clear'));
        $this->withProgress($bar, fn () => $this->runArtisan('migrate', ['--force', '--seed']));
        $this->withProgress($bar, fn () => $this->runProcess(['chown', '-R', "{$user}:{$group}", $this->getLaravel()->basePath()]));
        $this->withProgress($bar, fn () => $this->runArtisan('queue:restart'));
        $this->withProgress($bar, fn () => $this->runArtisan('up'));

        $this->newLine(2);
        $this->info('Panel has been successfully upgraded. Please ensure you also update any Wings instances: https://pterodactyl.io/docs/v2/wings/upgrading');

        return self::SUCCESS;
    }

    protected function withProgress(ProgressBar $bar, Closure $callback): void
    {
        $bar->clear();
        $callback();
        $bar->advance();
        $bar->display();
    }

    protected function getUrl(): string
    {
        if ($this->option('url')) {
            return $this->option('url');
        }

        return sprintf(self::DEFAULT_URL, $this->option('release') ? 'download/v'.$this->option('release') : 'latest/download');
    }

    private function download(FilesystemChanges $files): void
    {
        $directory = storage_path('app/.upgrade-'.Str::uuid());
        throw_unless(File::makeDirectory($directory, 0700, true), Exception::class, 'Unable to create a temporary upgrade directory.');
        $archive = $directory.DIRECTORY_SEPARATOR.'panel.tar.gz';

        try {
            $this->runProcess(['curl', '--fail', '--location', '--output', $archive, $this->getUrl()]);
            $this->runProcess(['tar', '-xzvf', $archive, '--directory', $this->getLaravel()->basePath()]);
        } finally {
            rescue(fn () => $files->delete($directory));
        }
    }

    /** @param list<string> $arguments */
    private function runArtisan(string $command, array $arguments = []): void
    {
        $this->runProcess([PHP_BINARY, 'artisan', $command, '--no-interaction', ...$arguments]);
    }

    /** @param list<string> $command */
    private function runProcess(array $command): void
    {
        $this->line('$upgrader> '.implode(' ', $command));
        Process::path($this->getLaravel()->basePath())
            ->timeout(10 * 60)
            ->run($command, fn (string $type, string $output) => $this->output->write($output))
            ->throw();
    }
}
