<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Pterodactyl\Console\Commands;

use Uuid;
use Illuminate\Console\Command;

class UpdateEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:env
                            {--dbhost=}
                            {--dbport=}
                            {--dbname=}
                            {--dbuser=}
                            {--dbpass=}
                            {--url=}
                            {--timezone=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update environment settings automatically.';

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
     * @return mixed
     */
    public function handle()
    {

        $variables = [];
        $file = base_path() . '/.env';
        if (!file_exists($file)) {
            $this->error('Missing environment file! It appears that you have not installed this panel correctly.');
            exit();
        }

        $envContents = file_get_contents($file);

        $this->info('Simply leave blank and press enter to fields that you do not wish to update.');
        if (!env('SERVICE_AUTHOR', false)) {
            $this->info('No service author set, setting one now.');
            $variables['SERVICE_AUTHOR'] = env('SERVICE_AUTHOR', (string) Uuid::generate(4));
        }

        if (!env('QUEUE_STANDARD', false) || !env('QUEUE_DRIVER', false)) {
            $this->info('Setting default queue settings.');
            $variables['QUEUE_DRIVER'] = env('QUEUE_DRIVER', 'database');
            $variables['QUEUE_HIGH'] = env('QUEUE_HIGH', 'high');
            $variables['QUEUE_STANDARD'] = env('QUEUE_STANDARD', 'standard');
            $variables['QUEUE_LOW'] = env('QUEUE_LOW', 'low');
        }

        if (is_null($this->option('dbhost'))) {
            $variables['DB_HOST'] = $this->anticipate('Database Host', [ 'localhost', '127.0.0.1', env('DB_HOST') ], env('DB_HOST'));
        } else {
            $variables['DB_HOST'] = $this->option('dbhost');
        }

        if (is_null($this->option('dbport'))) {
            $variables['DB_PORT'] = $this->anticipate('Database Port', [ 3306, env('DB_PORT') ], env('DB_PORT'));
        } else {
            $variables['DB_PORT'] = $this->option('dbport');
        }

        if (is_null($this->option('dbname'))) {
            $variables['DB_DATABASE'] = $this->anticipate('Database Name', [ 'pterodactyl', 'homestead', ENV('DB_DATABASE') ], env('DB_DATABASE'));
        } else {
            $variables['DB_DATABASE'] = $this->option('dbname');
        }

        if (is_null($this->option('dbuser'))) {
            $variables['DB_USERNAME'] = $this->anticipate('Database Username', [ ENV('DB_DATABASE') ], env('DB_USERNAME'));
        } else {
            $variables['DB_USERNAME'] = $this->option('dbuser');
        }

        if (is_null($this->option('dbpass'))) {
            $this->line('The Database Password field is required; you cannot hit enter and use a default value.');
            $variables['DB_PASSWORD'] = $this->secret('Database User Password');
        } else {
            $variables['DB_PASSWORD'] = $this->option('dbpass');
        }

        if (is_null($this->option('url'))) {
            $variables['APP_URL'] = $this->ask('Panel URL', env('APP_URL'));
        } else {
            $variables['APP_URL'] = $this->option('url');
        }

        if (is_null($this->option('timezone'))) {
            $this->line('The timezone should match one of the supported timezones according to http://php.net/manual/en/timezones.php');
            $variables['APP_TIMEZONE'] = $this->anticipate('Panel Timezone', \DateTimeZone::listIdentifiers(\DateTimeZone::ALL), env('APP_TIMEZONE'));
        } else {
            $variables['APP_TIMEZONE'] = $this->option('timezone');
        }

        $bar = $this->output->createProgressBar(count($variables));

        $this->line('Writing new environment configuration to file.');
        foreach ($variables as $key => $value) {
            $newValue = $key . '=' . $value;

            if (preg_match_all('/^' . $key . '=(.*)$/m', $envContents) < 1) {
                $envContents = $envContents . "\n" . $newValue;
            } else {
                $envContents = preg_replace('/^' . $key . '=(.*)$/m', $newValue, $envContents);
            }
            $bar->advance();
        }

        file_put_contents($file, $envContents);
        $bar->finish();
        echo "\n";
    }
}
