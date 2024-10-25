<?php

namespace Facebook\WebDriver\Remote\Service;

use Facebook\WebDriver\Exception\DriverServerDiedException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\HttpCommandExecutor;
use Facebook\WebDriver\Remote\WebDriverCommand;
use Facebook\WebDriver\Remote\WebDriverResponse;

/**
 * A HttpCommandExecutor that talks to a local driver service instead of a remote server.
 */
class DriverCommandExecutor extends HttpCommandExecutor
{
    /**
     * @var DriverService
     */
    private $service;

    public function __construct(DriverService $service)
    {
        parent::__construct($service->getURL());
        $this->service = $service;
    }

    /**
     * @param WebDriverCommand $command
     *
     * @throws \Exception
     * @throws WebDriverException
     * @return WebDriverResponse
     */
    public function execute(WebDriverCommand $command)
    {
        if ($command->getName() === DriverCommand::NEW_SESSION) {
            $this->service->start();
        }

        try {
            $value = parent::execute($command);
            if ($command->getName() === DriverCommand::QUIT) {
                $this->service->stop();
            }

            return $value;
        } catch (\Exception $e) {
            if (!$this->service->isRunning()) {
                throw new DriverServerDiedException($e);
            }
            throw $e;
        }
    }
}
