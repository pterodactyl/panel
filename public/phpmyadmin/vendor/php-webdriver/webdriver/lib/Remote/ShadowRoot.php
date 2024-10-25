<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Exception\UnknownErrorException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSearchContext;

class ShadowRoot implements WebDriverSearchContext
{
    /**
     * Shadow root identifier defined in the W3CWebDriver protocol.
     *
     * @see https://w3c.github.io/webdriver/#shadow-root
     */
    const SHADOW_ROOT_IDENTIFIER = 'shadow-6066-11e4-a52e-4f735466cecf';

    /**
     * @var RemoteExecuteMethod
     */
    private $executor;

    /**
     * @var string
     */
    private $id;

    public function __construct(RemoteExecuteMethod $executor, $id)
    {
        $this->executor = $executor;
        $this->id = $id;
    }

    /**
     * @param RemoteExecuteMethod $executor
     * @param array $response
     * @return self
     */
    public static function createFromResponse(RemoteExecuteMethod $executor, array $response)
    {
        if (empty($response[self::SHADOW_ROOT_IDENTIFIER])) {
            throw new UnknownErrorException('Shadow root is missing in server response');
        }

        return new self($executor, $response[self::SHADOW_ROOT_IDENTIFIER]);
    }

    /**
     * @param WebDriverBy $locator
     * @return RemoteWebElement
     */
    public function findElement(WebDriverBy $locator)
    {
        $params = JsonWireCompat::getUsing($locator, true);
        $params[':id'] = $this->id;

        $rawElement = $this->executor->execute(
            DriverCommand::FIND_ELEMENT_FROM_SHADOW_ROOT,
            $params
        );

        return new RemoteWebElement($this->executor, JsonWireCompat::getElement($rawElement), true);
    }

    /**
     * @param WebDriverBy $locator
     * @return WebDriverElement[]
     */
    public function findElements(WebDriverBy $locator)
    {
        $params = JsonWireCompat::getUsing($locator, true);
        $params[':id'] = $this->id;

        $rawElements = $this->executor->execute(
            DriverCommand::FIND_ELEMENTS_FROM_SHADOW_ROOT,
            $params
        );

        $elements = [];
        foreach ($rawElements as $rawElement) {
            $elements[] = new RemoteWebElement($this->executor, JsonWireCompat::getElement($rawElement), true);
        }

        return $elements;
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }
}
