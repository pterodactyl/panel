<?php

namespace Pterodactyl\Tests\Browser;

use Laravel\Dusk\TestCase;
use Tests\CreatesApplication;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Illuminate\Foundation\Testing\DatabaseMigrations;

abstract class BrowserTestCase extends TestCase
{
    use CreatesApplication, DatabaseMigrations;

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
        ]);

        return RemoteWebDriver::create(
            'http://services.pterodactyl.local:4444/wd/hub', DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Return an instance of the browser to be used for tests.
     *
     * @param \Facebook\WebDriver\Remote\RemoteWebDriver $driver
     * @return \Pterodactyl\Tests\Browser\PterodactylBrowser
     */
    protected function newBrowser($driver): PterodactylBrowser
    {
        return new PterodactylBrowser($driver);
    }
}
