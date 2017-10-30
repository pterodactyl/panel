<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Commands;

use Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    /**
     * @var bool
     */
    protected $commandIsInteractive = true;

    /**
     * Set a command to be non-interactive for testing purposes.
     *
     * @return $this
     */
    public function withoutInteraction()
    {
        $this->commandIsInteractive = false;

        return $this;
    }

    /**
     * Return the display from running a command.
     *
     * @param \Illuminate\Console\Command $command
     * @param array                       $args
     * @param array                       $inputs
     * @param array                       $opts
     * @return string
     */
    protected function runCommand(Command $command, array $args = [], array $inputs = [], array $opts = [])
    {
        if (! $command->getLaravel() instanceof Application) {
            $command->setLaravel($this->app);
        }

        $response = new CommandTester($command);
        $response->setInputs($inputs);

        $opts = array_merge($opts, ['interactive' => $this->commandIsInteractive]);
        $response->execute($args, $opts);

        return $response->getDisplay();
    }
}
