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

class UpdateEmailSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:mail';

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
        if (!file_exists($file)) {
            $this->error('Missing environment file! It appears that you have not installed this panel correctly.');
            exit();
        }

        $envContents = file_get_contents($file);

        $this->table([
            'Option',
            'Description'
        ], [
            [
                'smtp',
                'SMTP Server Email'
            ],
            [
                'mail',
                'PHP\'s Internal Mail Server'
            ],
            [
                'mailgun',
                'Mailgun Email Service'
            ],
            [
                'mandrill',
                'Mandrill Transactional Email Service'
            ],
            [
                'postmark',
                'Postmark Transactional Email Service'
            ]
        ]);
        $variables['MAIL_DRIVER'] = $this->choice('Which email driver would you like to use?', [
            'smtp',
            'mail',
            'mailgun',
            'mandrill',
            'postmark'
        ]);

        switch ($variables['MAIL_DRIVER']) {
            case 'smtp':
                $variables['MAIL_HOST'] = $this->ask('SMTP Host (e.g smtp.google.com)');
                $variables['MAIL_PORT'] = $this->anticipate('SMTP Host Port (e.g 587)', ['587']);
                $variables['MAIL_USERNAME'] = $this->ask('SMTP Username');
                $variables['MAIL_PASSWORD'] = $this->secret('SMTP Password');
                break;
            case 'mail':
                break;
            case 'mailgun':
                $variables['MAILGUN_DOMAIN'] = $this->ask('Mailgun Domain');
                $variables['MAILGUN_KEY'] = $this->ask('Mailgun Key');
                break;
            case 'mandrill':
                $variables['MANDRILL_SECRET'] = $this->ask('Mandrill Secret');
                break;
            case 'postmark':
                $variables['MAIL_DRIVER'] = 'smtp';
                $variables['MAIL_HOST'] = 'smtp.postmarkapp.com';
                $variables['MAIL_PORT'] = 587;
                $variables['MAIL_USERNAME'] = $this->ask('Postmark API Token');
                $variables['MAIL_PASSWORD'] = $variables['MAIL_USERNAME'];
                break;
            default:
                $this->error('No email service was defined!');
                exit();
                break;
        }

        $variables['MAIL_FROM'] = $this->ask('Email address emails should originate from');
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
        echo "\n";
    }
}
