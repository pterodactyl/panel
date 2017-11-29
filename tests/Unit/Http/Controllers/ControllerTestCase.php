<?php

namespace Tests\Unit\Http\Controllers;

use Mockery as m;
use Tests\TestCase;
use Tests\Traits\Http\RequestMockHelpers;
use Tests\Assertions\ControllerAssertionsTrait;

abstract class ControllerTestCase extends TestCase
{
    use ControllerAssertionsTrait, RequestMockHelpers;

    /**
     * @var \Pterodactyl\Http\Controllers\Controller|\Mockery\Mock
     */
    private $controller;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->buildRequestMock();
    }

    /**
     * Set an instance of the controller.
     *
     * @param \Pterodactyl\Http\Controllers\Controller|\Mockery\Mock $controller
     */
    public function setControllerInstance($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Return an instance of the controller.
     *
     * @return \Mockery\Mock|\Pterodactyl\Http\Controllers\Controller
     */
    public function getControllerInstance()
    {
        return $this->controller;
    }

    /**
     * Helper function to mock injectJavascript requests.
     *
     * @param array|null $args
     * @param bool       $subset
     */
    protected function mockInjectJavascript(array $args = null, bool $subset = false)
    {
        $controller = $this->getControllerInstance();

        $controller->shouldReceive('setRequest')->with($this->request)->once()->andReturnSelf();
        if (is_null($args)) {
            $controller->shouldReceive('injectJavascript')->withAnyArgs()->once()->andReturnNull();
        } else {
            $with = $subset ? m::subset($args) : $args;

            $controller->shouldReceive('injectJavascript')->with($with)->once()->andReturnNull();
        }
    }

    /**
     * Build and return a mocked controller instance to use for testing.
     *
     * @param string $class
     * @param array  $args
     * @return \Mockery\Mock|\Pterodactyl\Http\Controllers\Controller
     */
    protected function buildMockedController(string $class, array $args = [])
    {
        $controller = m::mock($class, $args)->makePartial();

        if (is_null($this->getControllerInstance())) {
            $this->setControllerInstance($controller);
        }

        return $this->getControllerInstance();
    }
}
