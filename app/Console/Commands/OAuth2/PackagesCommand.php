<?php

namespace Pterodactyl\Console\Commands\OAuth2;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class PackagesCommand extends Command
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:oauth2:packages
                            {--verbose-composer : Enable verbose command output.}';

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
     * @return void
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->config = $config;
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
            if (empty($this->config->get('oauth2.providers.' . $provider . '.package'))) continue;
            $packages = array_merge($packages, [$provider => $this->config->get('oauth2.providers.' . $provider . '.package')]);
        }

        if (empty($packages)) {
            $this->output->write(__('command/messages.environment.oauth2.no_packages'));
            return;
        }

        $this->output->write(__('command/messages.environment.oauth2.packages', ['number' => count($packages)]) . "\r\n");

        $command = 'composer require';
        if (file_exists('composer.phar')) {
            $command = 'php composer.phar require';
        }

        $bar = $this->output->createProgressBar(count($packages));

        $bar->start();

        \Log::info('[OAuth2 Package Installer]: Starting Installation...');
        foreach ($packages as $provider => $package) {
            \Log::info('[OAuth2 Package Installer]: Installing ' . $provider . ': ' . $package);
            $this->output->write(' ' . __('command/messages.environment.oauth2.installing', ['package' => $provider . ': ' . $package]));

            exec($command . ' --no-progress --no-suggest --no-update --no-scripts --update-no-dev 2>&1' . $package, $output);
            if ($this->option('verbose-composer')) {
                $this->output->write($output);
            }
            \Log::info('[OAuth2 Package Installer]: [Composer]: ' . implode("\r\n", $output));

            \Log::info('[OAuth2 Package Installer]: Installed ' . $provider . ': ' . $package);
            $this->output->write(' ' . __('command/messages.environment.oauth2.installed', ['package' => $provider . ': ' . $package]));
            $bar->advance();
        }

        $bar->finish();
        \Log::info('[OAuth2 Package Installer]: Installation finished');
        $this->output->write("\r\n" . __('command/messages.environment.oauth2.done', ['number' => count($packages)]));
    }
}
