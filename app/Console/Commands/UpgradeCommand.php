<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;

class UpgradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Pterodactyl to the latest version.';

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
        if (version_compare(PHP_VERSION, '7.4.0') < 0) {
            $this->error('Cannot execute self-upgrade process. The minimum required PHP version required is 7.4.0, you have [' . PHP_VERSION . '].');
            return 0;
        }
        
        $this->info('Updating Pterodactyl to the latest version');
        if ($this->confirm('Do you wish to continue?', true)) {


        /**
        * Commences update proccess and
        * executes commands below into terminal.
        */
        $this->command('php artisan down');
        $this->command('curl -L https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz | tar -xzv');
        $this->command('chmod -R 755 storage/* bootstrap/cache');
        $this->command('composer install --no-dev --optimize-autoloader');
        $this->command('php artisan view:clear && php artisan config:clear');
        $this->command('php artisan migrate --seed --force');
        $this->MakeChoice();
        $this->command('php artisan queue:restart');
        $this->command('php artisan up');
        $this->info('Update Complete - Successfully Installed the latest version of Pterodactyl Panel!');


        }
    }

    /**
     * Let user select for which OS to set permissions for.
     */
    private function MakeChoice()
    {
        // Check if operating system is Linux based
        $this->newLine(3);
        $this->info('Please select the correct permissions configuration');
        $this->line('1 => If using NGINX or Apache (not on CentOS)');
        $this->line('2 => If using NGINX on CentOS');
        $this->line('3 => If using Apache on CentOS');

        $choice = $this->anticipate('Select between 1, 2 or 3', ['1', '2', '3']) ;

        // Reflect choice to user
        if($choice == 1) {
            $this->info('Selected using NGINX or Apache (not on CentOS)');
            $this->command('chown -R www-data:www-data '.base_path().'/*');
        } elseif($choice == 2) {
            $this->info('Selected using NGINX on CentOS');
            $this->command('chown -R nginx:nginx '.base_path().'/*');
        } elseif($choice == 3) {
            $this->info('Selected using Apache on CentOS');
            $this->command('chown -R apache:apache '.base_path().'/*');
        } else {
            $this->error('Invalid configuration choice, choose between 1, 2 or 3');
            $this->MakeChoice();

        }
    }

    /**
     * executes commands into terminal
     */
    private function command($cmd)
    {
      return exec($cmd);
    }
}