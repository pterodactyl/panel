<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;

class update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update
        {--user= : The user that PHP runs under. All files will be owned by this user.}
        {--group= : The group that PHP runs under. All files will be owned by this group.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update your Dashboard to the latest version';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->output->warning('This command does just pull the newest changes from the github repo. Verify the github repo before running this');

        if (version_compare(PHP_VERSION, '8.0.0') < 0) {
            $this->error('Cannot execute self-upgrade process. The minimum required PHP version required is 8.0.0, you have ['.PHP_VERSION.'].');
        }

        $user = 'www-data';
        $group = 'www-data';
        if ($this->input->isInteractive()) {
            if (is_null($this->option('user'))) {
                $userDetails = posix_getpwuid(fileowner('public'));
                $user = $userDetails['name'] ?? 'www-data';

                if (! $this->confirm("Your webserver user has been detected as [{$user}]: is this correct?", true)) {
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

                if (! $this->confirm("Your webserver group has been detected as [{$group}]: is this correct?", true)) {
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

            ini_set('output_buffering', 0);

            if (! $this->confirm('Are you sure you want to run the upgrade process for your Dashboard?')) {
                return false;
            }

            $bar = $this->output->createProgressBar(9);
            $bar->start();

            $this->withProgress($bar, function () {
                $this->line('$upgrader> git pull');
                $process = Process::fromShellCommandline('git pull');
                $process->run(function ($type, $buffer) {
                    $this->{$type === Process::ERR ? 'error' : 'line'}($buffer);
                });
            });

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
                if (config('app.env') === 'production' && ! config('app.debug')) {
                    $command[] = '--optimize-autoloader';
                    $command[] = '--no-dev';
                }

                $this->line('$upgrader> '.implode(' ', $command));
                $process = new Process($command);
                $process->setTimeout(10 * 60);
                $process->run(function ($type, $buffer) {
                    $this->line($buffer);
                });
            });

            $this->withProgress($bar, function () {
                $this->line('$upgrader> php artisan view:clear');
                $this->call('view:clear');
            });

            $this->withProgress($bar, function () {
                $this->line('$upgrader> php artisan config:clear');
                $this->call('config:clear');
            });

            $this->withProgress($bar, function () {
                $this->line('$upgrader> php artisan migrate --force');
                $this->call('migrate', ['--force' => '']);
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
                $this->line('$upgrader> php artisan up');
                $this->call('up');
            });

            $this->newLine();
            $this->info('Finished running upgrade.');
        }
    }

    protected function withProgress(ProgressBar $bar, Closure $callback)
    {
        $bar->clear();
        $callback();
        $bar->advance();
        $bar->display();
    }
}
