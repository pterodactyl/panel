<?php

namespace Facebook\WebDriver\Chrome;

use Facebook\WebDriver\Local\LocalWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\Service\DriverCommandExecutor;
use Facebook\WebDriver\Remote\WebDriverCommand;

class ChromeDriver extends LocalWebDriver
{
    /** @var ChromeDevToolsDriver */
    private $devTools;

    /**
     * Creates a new ChromeDriver using default configuration.
     * This includes starting a new chromedriver process each time this method is called. However this may be
     * unnecessary overhead - instead, you can start the process once using ChromeDriverService and pass
     * this instance to startUsingDriverService() method.
     *
     * @todo Remove $service parameter. Use `ChromeDriver::startUsingDriverService` to pass custom $service instance.
     * @return static
     */
    public static function start(DesiredCapabilities $desired_capabilities = null, ChromeDriverService $service = null)
    {
        if ($service === null) { // TODO: Remove the condition (always create default service)
            $service = ChromeDriverService::createDefaultService();
        }

        return static::startUsingDriverService($service, $desired_capabilities);
    }

    /**
     * Creates a new ChromeDriver using given ChromeDriverService.
     * This is usable when you for example don't want to start new chromedriver process for each individual test
     * and want to reuse the already started chromedriver, which will lower the overhead associated with spinning up
     * a new process.

     * @return static
     */
    public static function startUsingDriverService(
        ChromeDriverService $service,
        DesiredCapabilities $capabilities = null
    ) {
        if ($capabilities === null) {
            $capabilities = DesiredCapabilities::chrome();
        }

        $executor = new DriverCommandExecutor($service);
        $newSessionCommand = WebDriverCommand::newSession(
            [
                'capabilities' => [
                    'firstMatch' => [(object) $capabilities->toW3cCompatibleArray()],
                ],
                'desiredCapabilities' => (object) $capabilities->toArray(),
            ]
        );

        $response = $executor->execute($newSessionCommand);

        /*
         * TODO: in next major version we may not need to use this method, because without OSS compatibility the
         * driver creation is straightforward.
         */
        return static::createFromResponse($response, $executor);
    }

    /**
     * @todo Remove in next major version. The class is internally no longer used and is kept only to keep BC.
     * @deprecated Use start or startUsingDriverService method instead.
     * @codeCoverageIgnore
     * @internal
     */
    public function startSession(DesiredCapabilities $desired_capabilities)
    {
        $command = WebDriverCommand::newSession(
            [
                'capabilities' => [
                    'firstMatch' => [(object) $desired_capabilities->toW3cCompatibleArray()],
                ],
                'desiredCapabilities' => (object) $desired_capabilities->toArray(),
            ]
        );
        $response = $this->executor->execute($command);
        $value = $response->getValue();

        if (!$this->isW3cCompliant = isset($value['capabilities'])) {
            $this->executor->disableW3cCompliance();
        }

        $this->sessionID = $response->getSessionID();
    }

    /**
     * @return ChromeDevToolsDriver
     */
    public function getDevTools()
    {
        if ($this->devTools === null) {
            $this->devTools = new ChromeDevToolsDriver($this);
        }

        return $this->devTools;
    }
}
