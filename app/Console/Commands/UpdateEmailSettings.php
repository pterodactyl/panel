<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
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

class UpdateEmailSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:mail
                            {--driver=}
                            {--email=}
                            {--from-name=}
                            {--host=}
                            {--port=}
                            {--username=}
                            {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets or updates email settings for the .env file.';

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
        if (! file_exists($file)) {
            $this->error('Missing environment file! It appears that you have not installed this panel correctly.');
            exit();
        }

        $envContents = file_get_contents($file);

        $this->table([
            'Option',
            'Description',
        ], [
            [
                'smtp',
                'SMTP Server Email',
            ],
            [
                'mail',
                'PHP\'s Internal Mail Server',
            ],
            [
                'mailgun',
                'Mailgun Email Service',
            ],
            [
                'mandrill',
                'Mandrill Transactional Email Service',
            ],
            [
                'postmark',
                'Postmark Transactional Email Service',
            ],
        ]);
        $variables['MAIL_DRIVER'] = is_null($this->option('driver')) ? $this->choice('Which email driver would you like to use?', [
            'smtp',
            'mail',
            'mailgun',
            'mandrill',
            'postmark',
        ]) : $this->option('driver');

        switch ($variables['MAIL_DRIVER']) {
            case 'smtp':
                $variables['MAIL_HOST'] = is_null($this->option('host')) ? $this->ask('SMTP Host (e.g smtp.google.com)') : $this->option('host');
                $variables['MAIL_PORT'] = is_null($this->option('port')) ? $this->anticipate('SMTP Host Port (e.g 587)', ['587']) : $this->option('port');
                $variables['MAIL_USERNAME'] = is_null($this->option('username')) ? $this->ask('SMTP Username') : $this->option('password');
                $variables['MAIL_PASSWORD'] = is_null($this->option('password')) ? $this->secret('SMTP Password') : $this->option('password');
                break;
            case 'mail':
                break;
            case 'mailgun':
                $variables['MAILGUN_DOMAIN'] = is_null($this->option('host')) ? $this->ask('Mailgun Domain') : $this->option('host');
                $variables['MAILGUN_KEY'] = is_null($this->option('username')) ? $this->ask('Mailgun Key') : $this->option('username');
                break;
            case 'mandrill':
                $variables['MANDRILL_SECRET'] = is_null($this->option('username')) ? $this->ask('Mandrill Secret') : $this->option('username');
                break;
            case 'postmark':
                $variables['MAIL_DRIVER'] = 'smtp';
                $variables['MAIL_HOST'] = 'smtp.postmarkapp.com';
                $variables['MAIL_PORT'] = 587;
                $variables['MAIL_USERNAME'] = is_null($this->option('username')) ? $this->ask('Postmark API Token') : $this->option('username');
                $variables['MAIL_PASSWORD'] = $variables['MAIL_USERNAME'];
                break;
            default:
                $this->error('No email service was defined!');
                exit();
                break;
        }

        $variables['MAIL_FROM'] = is_null($this->option('email')) ? $this->ask('Email address emails should originate from') : $this->option('email');
        $variables['MAIL_FROM_NAME'] = is_null($this->option('from-name')) ? $this->ask('Name emails should appear to be from') : $this->option('from-name');
        $variables['MAIL_ENCRYPTION'] = 'tls';

        $bar = $this->output->createProgressBar(count($variables));

        $this->line('Writing new email environment configuration to file.');
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

        $this->line('Updating evironment configuration cache file.');
        $this->call('config:cache');
        echo "\n";
    }
}
