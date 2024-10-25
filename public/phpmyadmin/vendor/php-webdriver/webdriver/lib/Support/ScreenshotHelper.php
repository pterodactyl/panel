<?php

namespace Facebook\WebDriver\Support;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\RemoteExecuteMethod;

/**
 * Helper class to handle taking, decoding and screenshots using WebDriver.
 */
class ScreenshotHelper
{
    /** @var RemoteExecuteMethod */
    private $executor;

    public function __construct(RemoteExecuteMethod $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @param string|null $saveAs
     * @throws WebDriverException
     * @return string
     */
    public function takePageScreenshot($saveAs = null)
    {
        $commandToExecute = [DriverCommand::SCREENSHOT];

        return $this->takeScreenshot($commandToExecute, $saveAs);
    }

    public function takeElementScreenshot($elementId, $saveAs = null)
    {
        $commandToExecute = [DriverCommand::TAKE_ELEMENT_SCREENSHOT, [':id' => $elementId]];

        return $this->takeScreenshot($commandToExecute, $saveAs);
    }

    private function takeScreenshot(array $commandToExecute, $saveAs = null)
    {
        $response = $this->executor->execute(...$commandToExecute);

        if (!is_string($response)) {
            throw new WebDriverException('Error taking screenshot, no data received from the remote end');
        }

        $screenshot = base64_decode($response, true);

        if ($screenshot === false) {
            throw new WebDriverException('Error decoding screenshot data');
        }

        if ($saveAs !== null) {
            $this->saveScreenshotToPath($screenshot, $saveAs);
        }

        return $screenshot;
    }

    private function saveScreenshotToPath($screenshot, $path)
    {
        $this->createDirectoryIfNotExists(dirname($path));

        file_put_contents($path, $screenshot);
    }

    private function createDirectoryIfNotExists($directoryPath)
    {
        if (!file_exists($directoryPath)) {
            if (!mkdir($directoryPath, 0777, true) && !is_dir($directoryPath)) {
                throw new WebDriverException(sprintf('Directory "%s" was not created', $directoryPath));
            }
        }
    }
}
