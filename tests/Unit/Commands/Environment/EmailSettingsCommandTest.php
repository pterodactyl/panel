<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Commands\Environment;

use Mockery as m;
use Tests\Unit\Commands\CommandTestCase;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Console\Commands\Environment\EmailSettingsCommand;

class EmailSettingsCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\Environment\EmailSettingsCommand|\Mockery\Mock
     */
    protected $command;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->command = m::mock(EmailSettingsCommand::class . '[call, writeToEnvironment]', [$this->config]);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test selection of the SMTP driver with no options passed.
     */
    public function testSmtpDriverSelection()
    {
        $data = [
            'MAIL_DRIVER' => 'smtp',
            'MAIL_HOST' => 'mail.test.com',
            'MAIL_PORT' => '567',
            'MAIL_USERNAME' => 'username',
            'MAIL_PASSWORD' => 'password',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->runCommand($this->command, [], array_values($data));

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test that the command can run when all variables are passed in as options.
     */
    public function testSmtpDriverSelectionWithOptionsPassed()
    {
        $data = [
            'MAIL_DRIVER' => 'smtp',
            'MAIL_HOST' => 'mail.test.com',
            'MAIL_PORT' => '567',
            'MAIL_USERNAME' => 'username',
            'MAIL_PASSWORD' => 'password',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--driver' => $data['MAIL_DRIVER'],
            '--email' => $data['MAIL_FROM'],
            '--from' => $data['MAIL_FROM_NAME'],
            '--encryption' => $data['MAIL_ENCRYPTION'],
            '--host' => $data['MAIL_HOST'],
            '--port' => $data['MAIL_PORT'],
            '--username' => $data['MAIL_USERNAME'],
            '--password' => $data['MAIL_PASSWORD'],
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test selection of PHP mail() as the driver.
     */
    public function testPHPMailDriverSelection()
    {
        $data = [
            'MAIL_DRIVER' => 'mail',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);

        // The driver flag is passed because there seems to be some issue with the command tester
        // when using a choice() method when two keys start with the same letters.
        //
        // In this case, mail and mailgun.
        unset($data['MAIL_DRIVER']);
        $display = $this->runCommand($this->command, ['--driver' => 'mail'], array_values($data));

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test selection of the Mailgun driver with no options passed.
     */
    public function testMailgunDriverSelection()
    {
        $data = [
            'MAIL_DRIVER' => 'mailgun',
            'MAILGUN_DOMAIN' => 'domain.com',
            'MAILGUN_SECRET' => '123456',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->runCommand($this->command, [], array_values($data));

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test mailgun driver selection when variables are passed as options.
     */
    public function testMailgunDriverSelectionWithOptionsPassed()
    {
        $data = [
            'MAIL_DRIVER' => 'mailgun',
            'MAILGUN_DOMAIN' => 'domain.com',
            'MAILGUN_SECRET' => '123456',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--driver' => $data['MAIL_DRIVER'],
            '--email' => $data['MAIL_FROM'],
            '--from' => $data['MAIL_FROM_NAME'],
            '--encryption' => $data['MAIL_ENCRYPTION'],
            '--host' => $data['MAILGUN_DOMAIN'],
            '--password' => $data['MAILGUN_SECRET'],
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test selection of the Mandrill driver with no options passed.
     */
    public function testMandrillDriverSelection()
    {
        $data = [
            'MAIL_DRIVER' => 'mandrill',
            'MANDRILL_SECRET' => '123456',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->runCommand($this->command, [], array_values($data));

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test mandrill driver selection when variables are passed as options.
     */
    public function testMandrillDriverSelectionWithOptionsPassed()
    {
        $data = [
            'MAIL_DRIVER' => 'mandrill',
            'MANDRILL_SECRET' => '123456',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--driver' => $data['MAIL_DRIVER'],
            '--email' => $data['MAIL_FROM'],
            '--from' => $data['MAIL_FROM_NAME'],
            '--encryption' => $data['MAIL_ENCRYPTION'],
            '--password' => $data['MANDRILL_SECRET'],
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test selection of the Postmark driver with no options passed.
     */
    public function testPostmarkDriverSelection()
    {
        $data = [
            'MAIL_DRIVER' => 'smtp',
            'MAIL_HOST' => 'smtp.postmarkapp.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '123456',
            'MAIL_PASSWORD' => '123456',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->runCommand($this->command, [], [
            'postmark', '123456', $data['MAIL_FROM'], $data['MAIL_FROM_NAME'], $data['MAIL_ENCRYPTION'],
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Test postmark driver selection when variables are passed as options.
     */
    public function testPostmarkDriverSelectionWithOptionsPassed()
    {
        $data = [
            'MAIL_DRIVER' => 'smtp',
            'MAIL_HOST' => 'smtp.postmarkapp.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '123456',
            'MAIL_PASSWORD' => '123456',
            'MAIL_FROM' => 'mail@from.com',
            'MAIL_FROM_NAME' => 'MailName',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        $this->setupCoreFunctions($data);
        $display = $this->withoutInteraction()->runCommand($this->command, [
            '--driver' => 'postmark',
            '--email' => $data['MAIL_FROM'],
            '--from' => $data['MAIL_FROM_NAME'],
            '--encryption' => $data['MAIL_ENCRYPTION'],
            '--username' => $data['MAIL_USERNAME'],
        ]);

        $this->assertNotEmpty($display);
        $this->assertContains('Updating stored environment configuration file.', $display);
    }

    /**
     * Setup the core functions that are repeated across all of these tests.
     *
     * @param array $data
     */
    private function setupCoreFunctions(array $data)
    {
        $this->config->shouldReceive('get')->withAnyArgs()->zeroOrMoreTimes()->andReturnNull();
        $this->command->shouldReceive('writeToEnvironment')->with($data)->once()->andReturnNull();
    }
}
