<?php

namespace Pterodactyl\Console\Commands;

use Closure;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Pterodactyl\Console\Kernel;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\ProgressBar;

class UpgradeCommand extends Command
{
    protected const DEFAULT_URL = 'https://github.com/pterodactyl/panel/releases/%s/panel.tar.gz';

    /** @var string */
    protected $signature = 'p:upgrade
        {--user= : The user that PHP runs under. All files will be owned by this user.}
        {--group= : The group that PHP runs under. All files will be owned by this group.}
        {--url= : The specific archive to download.}
        {--release= : A specific Pterodactyl version to download from GitHub. Leave blank to use latest.}
        {--skip-download : If set no archive will be downloaded.}';

    /** @var string */
    protected $description = 'Downloads a new archive for Pterodactyl from GitHub and then executes the normal upgrade commands.';

    /**
     * Executes an upgrade command which will run through all of our standard
     * commands for Pterodactyl and enable users to basically just download
     * the archive and execute this and be done.
     *
     * This places the application in maintenance mode as well while the commands
     * are being executed.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $skipDownload = $this->option('skip-download');
        if (!$skipDownload) {
            $this->output->warning('This command does not verify the integrity of downloaded assets. Please ensure that you trust the download source before continuing. If you do not wish to download an archive, please indicate that using the --skip-download flag, or answering "no" to the question below.');
            $this->output->comment('Download Source (set with --url=):');
            $this->line($this->getUrl());
        }

        if (version_compare(PHP_VERSION, '7.4.0') < 0) {
            $this->error('Cannot execute self-upgrade process. The minimum required PHP version required is 7.4.0, you have [' . PHP_VERSION . '].');
        }

        $user = 'www-data';
        $group = 'www-data';
        if ($this->input->isInteractive()) {
            if (!$skipDownload) {
                $skipDownload = !$this->confirm('Would you like to download and unpack the archive files for the latest version?', true);
            }

            if (is_null($this->option('user'))) {
                $userDetails = posix_getpwuid(fileowner('public'));
                $user = $userDetails['name'] ?? 'www-data';

                if (!$this->confirm("Your webserver user has been detected as <fg=blue>[{$user}]:</> is this correct?", true)) {
                    $user = $this->anticipate(
                        'Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".',
                        [
                            'www-data',
                            'nginx',
                            'apache',
                        ]
                    );
                }
            }

            if (is_null($this->option('group'))) {
                $groupDetails = posix_getgrgid(filegroup('public'));
                $group = $groupDetails['name'] ?? 'www-data';

                if (!$this->confirm("Your webserver group has been detected as <fg=blue>[{$group}]:</> is this correct?", true)) {
                    $group = $this->anticipate(
                        'Please enter the name of the group running your webserver process. Normally this is the same as your user.',
                        [
                            'www-data',
                            'nginx',
                            'apache',
                        ]
                    );
                }
            }

            if (!$this->confirm('Are you sure you want to run the upgrade process for your Panel?')) {
                $this->warn('Upgrade process terminated by user.');

                return;
            }
        }

        ini_set('output_buffering', 0);
        $bar = $this->output->createProgressBar($skipDownload ? 8 : 13);
        $bar->start();
        $dir = sprintf('/var/www/backup_%s', preg_replace('/:|\+/', '_', CarbonImmutable::now()->toIso8601String()));

        if (!$skipDownload) {
            $this->withProgress($bar, function () {
                $this->line("\$upgrader> curl -L \"{$this->getUrl()}\" -o /tmp/panel.tar.gz");
                $process = Process::fromShellCommandline("curl -L \"{$this->getUrl()}\" -o /tmp/panel.tar.gz");
                $process->run(function ($type, $buffer) {
                    $this->{$type === Process::ERR ? 'error' : 'line'}($buffer);
                    if ($type === Process:ERR) {
                        return $this->processError(false);
                    }
                });
            });
            
            $this->withProgress($bar, function () {
                $this->line("\$upgrader> mkdir ${$dir}");
                Process::fromShellCommandline("mkdir ${$dir}")->run(function ($type, $buffer) {
                    if ($type === Process::ERR) {
                        return $this->processError(false, $buffer);
                    }
                });
            });
            
            $this->withProgress($bar, function () {
                $this->line("\$upgrader> mv /var/www/pterodactyl/* ${$dir}");
                Process::fromShellCommandline("mv /var/www/pterodactyl/* ${$dir}")->run(function ($type, $buffer) {
                    if ($type === Process:ERR) {
                        return $this->processError(false, $buffer);
                    }
                    
                    $this->info("Current panel has been backed up. If the upgrade fails, you can restore your files at ${$dir}");
                });
            });
            
            $this->withProgress($bar, function () {
                $this->line('\$upgrader> mv /tmp/panel.tar.gz /var/www/pterodactyl');
                $process = Process::fromShellCommandline('mv /tmp/panel.tar.gz /var/www/pterodactyl');
                $process->run(function ($type, $buffer) {
                    $this->{$type === Process::ERR ? 'error' : 'line'}($buffer);
                    if ($type === Process::ERR) {
                        return $this->processError(true);
                    }
                });
            });
            
            $this->withProgress($bar, function () {
                $this->line('\$upgrader> cd /var/www/pterodactyl && tar -xzvf panel.tar.gz');
                $process = Process::fromShellCommandline('cd /var/www/pterodactyl && tar -xzvf panel.tar.gz');
                $process->run(function ($type, $buffer) {
                    $this->{$type === Process::ERR ? 'error' : 'line'}($buffer);
                    if ($type === Process::ERR) {
                        return $this->processError(true);
                    }
                });
            });
        }

        $this->withProgress($bar, function () {
            $this->line('$upgrader> php artisan down');
            $this->call('down');
        });

        $this->withProgress($bar, function () {
            $this->line('$upgrader> chmod -R 755 storage bootstrap/cache');
            $process = new Process(['chmod', '-R', '755', 'storage', 'bootstrap/cache']);
            $process->run(function ($type, $buffer) {
                $this->{$type === Process::ERR ? 'error' : 'line'}($buffer);
            });
        });

        $this->withProgress($bar, function () {
            $command = ['composer', 'install', '--no-ansi'];
            if (config('app.env') === 'production' && !config('app.debug')) {
                $command[] = '--optimize-autoloader';
                $command[] = '--no-dev';
            }

            $this->line('$upgrader> ' . implode(' ', $command));
            $process = new Process($command);
            $process->setTimeout(10 * 60);
            $process->run(function ($type, $buffer) {
                $this->line($buffer);
            });
        });

        /** @var \Illuminate\Foundation\Application $app */
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        /** @var \Pterodactyl\Console\Kernel $kernel */
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();
        $this->setLaravel($app);

        $this->withProgress($bar, function () {
            $this->line('$upgrader> php artisan optimize:clear');
            $this->call('optimize:clear');
        });

        $this->withProgress($bar, function () {
            $this->line('$upgrader> php artisan migrate --force --seed');
            $this->call('migrate', ['--force' => true, '--seed' => true]);
        });

        $this->withProgress($bar, function () use ($user, $group) {
            $this->line("\$upgrader> chown -R {$user}:{$group} *");
            $process = Process::fromShellCommandline("chown -R {$user}:{$group} *", $this->getLaravel()->basePath());
            $process->setTimeout(10 * 60);
            $process->run(function ($type, $buffer) {
                $this->{$type === Process::ERR ? 'error' : 'line'}($buffer);
            });
        });

        $this->withProgress($bar, function () {
            $this->line('$upgrader> php artisan queue:restart');
            $this->call('queue:restart');
        });

        $this->withProgress($bar, function () {
            $this->line('$upgrader> php artisan up');
            $this->call('up');
        });

        $this->newLine(2);
        $this->info('Panel has been successfully upgraded. Please ensure you also update any Wings instances: https://pterodactyl.io/wings/1.0/upgrading.html');
    }

    protected function withProgress(ProgressBar $bar, Closure $callback)
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

        return sprintf(self::DEFAULT_URL, $this->option('release') ? 'download/v' . $this->option('release') : 'latest/download');
    }
    
    protected function processError($restore, $data = null)
    {
        if (!!$data) {
            $this->error($data);
        }
        
        if ($restore) {
            $this->info('Attempting to restore latest backup.');
            $process = Process::fromShellCommandline("mv ${$dir} /var/www/pterodactyl");
            $process->run(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    $this->error($buffer);
                    $this->error('Failed to rollback to previous panel version.');
                    $this->error("You can do this manually by moving the files from ${$dir} to the default path.");
                } else {
                    $this->info('Rolled back to previous panel version.');
                }
            });
        }
        
        Process::fromShellCommandline('rm -f /tmp/panel.tar.gz')->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                $this->error('Failed to remove archive from tmp folder.');
            }
        });
    }
}
