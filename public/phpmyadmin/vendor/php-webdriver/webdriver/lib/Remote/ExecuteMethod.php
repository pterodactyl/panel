<?php

namespace Facebook\WebDriver\Remote;

interface ExecuteMethod
{
    /**
     * @param string $command_name
     * @param array $parameters
     * @return WebDriverResponse
     */
    public function execute($command_name, array $parameters = []);
}
