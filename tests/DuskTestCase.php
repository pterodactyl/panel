<?php

namespace Tests;

use Tests\Traits\DatabaseTruncations;
use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        return RemoteWebDriver::create(
            'http://localhost:9515', DesiredCapabilities::chrome()
        );
    }

    public function setUp()
    {
        parent::setUp();
        $this->createApplication();

        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[DatabaseTruncations::class])) {
            $this->runDatabaseTruncations();
        }
    }
}
