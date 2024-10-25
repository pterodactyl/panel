<?php

namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\WebDriverCommand;
use Facebook\WebDriver\Remote\WebDriverResponse;

/**
 * Interface for all command executor.
 */
interface WebDriverCommandExecutor
{
    /**
     * @param WebDriverCommand $command
     *
     * @return WebDriverResponse
     */
    public function execute(WebDriverCommand $command);
}
