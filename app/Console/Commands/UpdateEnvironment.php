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

use Illuminate\Console\Command;

class UpdateEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:env';

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

        // DB info
        $variables['DB_HOST'] = $this->anticipate('Database Host (usually \'localhost\' or \'127.0.0.1\')', [ 'localhost', '127.0.0.1', env('DB_HOST') ]);
        $variables['DB_PORT'] = $this->anticipate('Database Port', [ 3306, env('DB_PORT') ]);
        $variables['DB_DATABASE'] = $this->anticipate('Database Name', [ 'pterodactyl', 'homestead', ENV('DB_DATABASE') ]);
        $variables['DB_USERNAME'] = $this->anticipate('Database Username', [ env('DB_USERNAME') ]);
        $variables['DB_PASSWORD'] = $this->secret('Database User\'s Password');

        // Other Basic Information
        $variables['APP_URL'] = $this->anticipate('Enter your current panel URL (include http or https).', [ env('APP_URL', 'http://localhost') ]);
        $this->line('The timezone should match one of the supported timezones according to http://php.net/manual/en/timezones.php');
        $variables['APP_TIMEZONE'] = $this->anticipate('Enter the timezone for this panel to run with', \DateTimeZone::listIdentifiers(\DateTimeZone::ALL));

        $bar = $this->output->createProgressBar(count($variables) + 1);

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
