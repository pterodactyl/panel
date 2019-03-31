<?php

namespace Pterodactyl\Console\Commands\OAuth2;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface as Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

class PackagesCommand extends Command
{

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:oauth2:packages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install packages used for OAuth2 providers.';

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param Log $log
     */
    public function __construct(ConfigRepository $config, Log $log)
    {
        parent::__construct();

        $this->config = $config;
        $this->log = $log;
    }

    /**
     * Execute the console command.
     *
     * @return mixed|void
     */
    public function handle()
    {
        $packages = [];

        foreach (preg_split('~,~', $this->config->get('oauth2.all_drivers')) as $provider) {
            if (empty($this->config->get('oauth2.providers.' . $provider . '.package'))) {
                continue;
            }
            $packages = array_merge($packages, [$provider => $this->config->get('oauth2.providers.' . $provider . '.package')]);
        }

        if (empty($packages)) {
            $this->output->write(__('command/messages.environment.oauth2.no_packages'));

            return;
        }

        $this->output->write(__('command/messages.environment.oauth2.packages', ['number' => count($packages)]) . "\r\n");

        $command = 'composer';
        if (file_exists('composer.phar')) {
            $command = 'php composer.phar';
        }

        $bar = $this->output->createProgressBar(count($packages));

        $bar->start();

        $failed = 0;

        $this->log->info('[OAuth2 Package Installer]: Starting Installation...');
        foreach ($packages as $provider => $package) {
            $this->log->info('[OAuth2 Package Installer]: Installing ' . $provider . ': ' . $package);
            $this->output->write(' ' . __('command/messages.environment.oauth2.installing', ['package' => $provider . ': ' . $package]));

            $process = (new Process(preg_split('~\s+~', $command . str_replace(':package', $package, ' require :package --no-progress --no-suggest --no-update --no-scripts --update-no-dev'))))->setTimeout(null);

            try {
                $process->setTty(false);
            } catch (RuntimeException $e) {
                $this->output->writeln('Warning: '.$e->getMessage());
            }
            $process->run(function ($type, $line) {
                if ($this->option('verbose')) {
                    $this->output->write('[Composer]: ' . $line);
                }
                $this->log->info('[OAuth2 Package Installer]: [Composer]: ' . $line);
            });

            if ($process->isSuccessful()) {
                $this->log->info('[OAuth2 Package Installer]: Installed ' . $provider . ': ' . $package);
                $this->output->write(' ' . __('command/messages.environment.oauth2.installed', ['package' => $provider . ': ' . $package]));
            } else {
                $failed++;
                $this->log->info('[OAuth2 Package Installer]: Failed installation of ' . $provider . ': ' . $package);
                $this->output->write(' ' . __('command/messages.environment.oauth2.failed', ['package' => $provider . ': ' . $package]));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->log->info('[OAuth2 Package Installer]: Installation finished');
        $this->output->write("\r\n" . __('command/messages.environment.oauth2.done', ['number' => count($packages) - $failed]));
        $this->output->write("\r\n" . __('command/messages.environment.oauth2.done_failed', ['number' => $failed]));
    }
}
