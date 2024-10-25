<?php

namespace Facebook\WebDriver\Interactions\Internal;

use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\WebDriverPoint;

/**
 * Interface representing basic mouse operations.
 */
class WebDriverCoordinates
{
    /**
     * @var null
     */
    private $onScreen;
    /**
     * @var callable
     */
    private $inViewPort;
    /**
     * @var callable
     */
    private $onPage;
    /**
     * @var string
     */
    private $auxiliary;

    /**
     * @param null $on_screen
     * @param callable $in_view_port
     * @param callable $on_page
     * @param string $auxiliary
     */
    public function __construct($on_screen, callable $in_view_port, callable $on_page, $auxiliary)
    {
        $this->onScreen = $on_screen;
        $this->inViewPort = $in_view_port;
        $this->onPage = $on_page;
        $this->auxiliary = $auxiliary;
    }

    /**
     * @throws UnsupportedOperationException
     * @return WebDriverPoint
     */
    public function onScreen()
    {
        throw new UnsupportedOperationException(
            'onScreen is planned but not yet supported by Selenium'
        );
    }

    /**
     * @return WebDriverPoint
     */
    public function inViewPort()
    {
        return call_user_func($this->inViewPort);
    }

    /**
     * @return WebDriverPoint
     */
    public function onPage()
    {
        return call_user_func($this->onPage);
    }

    /**
     * @return string The attached object id.
     */
    public function getAuxiliary()
    {
        return $this->auxiliary;
    }
}
