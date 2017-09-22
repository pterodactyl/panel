<?php
/*
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

namespace Pterodactyl\Console\Commands\Environment;

use Illuminate\Console\Command;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class EmailSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var string
     */
    protected $description = 'Set or update the email sending configuration for the Panel.';

    /**
     * @var string
     */
    protected $signature = 'p:environment:mail
                            {--driver= : The mail driver to use.}
                            {--email= : Email address that messages from the Panel will originate from.}
                            {--from= : The name emails from the Panel will appear to be from.}
                            {--encryption=}
                            {--host=}
                            {--port=}
                            {--username=}
                            {--password=}';

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * EmailSettingsCommand constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $this->variables['MAIL_DRIVER'] = $this->option('driver') ?? $this->choice(
            trans('command/messages.environment.mail.ask_driver'), [
                'smtp' => 'SMTP Server',
                'mail' => 'PHP\'s Internal Mail Function',
                'mailgun' => 'Mailgun Transactional Email',
                'mandrill' => 'Mandrill Transactional Email',
                'postmark' => 'Postmarkapp Transactional Email',
            ], $this->config->get('mail.driver', 'smtp')
        );

        $method = 'setup' . studly_case($this->variables['MAIL_DRIVER']) . 'DriverVariables';
        if (method_exists($this, $method)) {
            $this->{$method}();
        }

        $this->variables['MAIL_FROM'] = $this->option('email') ?? $this->ask(
            trans('command/messages.environment.mail.ask_mail_from'), $this->config->get('mail.from.address')
        );

        $this->variables['MAIL_FROM_NAME'] = $this->option('from') ?? $this->ask(
            trans('command/messages.environment.mail.ask_mail_name'), $this->config->get('mail.from.name')
        );

        $this->variables['MAIL_ENCRYPTION'] = $this->option('encryption') ?? $this->choice(
            trans('command/messages.environment.mail.ask_encryption'), ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'], $this->config->get('mail.encryption', 'tls')
        );

        $this->writeToEnvironment($this->variables);

        $this->line('Updating stored environment configuration file.');
        $this->call('config:cache');
        $this->line('');
    }

    /**
     * Handle variables for SMTP driver.
     */
    private function setupSmtpDriverVariables()
    {
        $this->variables['MAIL_HOST'] = $this->option('host') ?? $this->ask(
            trans('command/messages.environment.mail.ask_smtp_host'), $this->config->get('mail.host')
        );

        $this->variables['MAIL_PORT'] = $this->option('port') ?? $this->ask(
            trans('command/messages.environment.mail.ask_smtp_port'), $this->config->get('mail.port')
        );

        $this->variables['MAIL_USERNAME'] = $this->option('username') ?? $this->ask(
            trans('command/messages.environment.mail.ask_smtp_username'), $this->config->get('mail.username')
        );

        $this->variables['MAIL_PASSWORD'] = $this->option('password') ?? $this->secret(
            trans('command/messages.environment.mail.ask_smtp_password')
        );
    }

    /**
     * Handle variables for mailgun driver.
     */
    private function setupMailgunDriverVariables()
    {
        $this->variables['MAILGUN_DOMAIN'] = $this->option('host') ?? $this->ask(
            trans('command/messages.environment.mail.ask_mailgun_domain'), $this->config->get('services.mailgun.domain')
        );

        $this->variables['MAILGUN_KEY'] = $this->option('password') ?? $this->ask(
            trans('command/messages.environment.mail.ask_mailgun_secret'), $this->config->get('services.mailgun.secret')
        );
    }

    /**
     * Handle variables for mandrill driver.
     */
    private function setupMandrillDriverVariables()
    {
        $this->variables['MANDRILL_SECRET'] = $this->option('password') ?? $this->ask(
            trans('command/messages.environment.mail.ask_mandrill_secret'), $this->config->get('services.mandrill.secret')
        );
    }

    /**
     * Handle variables for postmark driver.
     */
    private function setupPostmarkDriverVariables()
    {
        $this->variables['MAIL_DRIVER'] = 'smtp';
        $this->variables['MAIL_HOST'] = 'smtp.postmarkapp.com';
        $this->variables['MAIL_PORT'] = 587;
        $this->variables['MAIL_USERNAME'] = $this->variables['MAIL_PASSWORD'] = $this->option('username') ?? $this->ask(
            trans('command/messages.environment.mail.ask_postmark_username'), $this->config->get('mail.username')
        );
    }
}
