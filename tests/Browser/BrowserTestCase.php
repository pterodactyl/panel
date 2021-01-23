<?php

namespace Pterodactyl\Tests\Browser;

use Laravel\Dusk\TestCase;
use BadMethodCallException;
use Pterodactyl\Models\User;
use Pterodactyl\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Tests\CreatesApplication;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class BrowserTestCase extends TestCase
{
    use CreatesApplication;

    /**
     * The default password to use for new accounts.
     *
     * @var string
     */
    protected static $userPassword = 'Password123';

    /**
     * Create a fresh database instance before each test class is initialized. This is different
     * than the default DatabaseMigrations as it is only run when the class is setup. The trait
     * provided by Laravel will run on EACH test function, slowing things down significantly.
     *
     * If you need to reset the DB between function runs just include the trait in that specific
     * test. In most cases you probably wont need to do this, or can modify the test slightly to
     * avoid the need to do so.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $app = require __DIR__ . '/../../bootstrap/app.php';

        /** @var \Pterodactyl\Console\Kernel $kernel */
        $kernel = $app->make(Kernel::class);

        $kernel->bootstrap();
        $kernel->call('migrate:fresh');
    }

    /**
     * Setup tests.
     */
    protected function setUp(): void
    {
        // Don't accidentally run the migrations aganist the non-testing database. Ask me
        // how many times I've accidentally dropped my database...
        if (env('DB_CONNECTION') !== 'testing') {
            throw new BadMethodCallException('Cannot call browser tests using the non-testing database connection.');
        }

        parent::setUp();

        // Gotta unset this to continue avoiding issues with the validation.
        Model::unsetEventDispatcher();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions())->addArguments([
            '--disable-gpu',
            '--disable-infobars',
        ]);

        return RemoteWebDriver::create(
            'http://host.pterodactyl.local:4444/wd/hub',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Return an instance of the browser to be used for tests.
     *
     * @param \Facebook\WebDriver\Remote\RemoteWebDriver $driver
     *
     * @return \Pterodactyl\Tests\Browser\PterodactylBrowser
     */
    protected function newBrowser($driver): PterodactylBrowser
    {
        return new PterodactylBrowser($driver);
    }

    /**
     * Tear down the test and delete all cookies from the browser instance to address
     * instances where the test would be kicked over to the login page.
     */
    protected function tearDown(): void
    {
        /** @var \Pterodactyl\Tests\Browser\PterodactylBrowser $browser */
        foreach (static::$browsers as $browser) {
            $browser->driver->manage()->deleteAllCookies();
        }

        parent::tearDown();
    }

    /**
     * Return a user model to authenticate aganist and use in the tests.
     */
    protected function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make(static::$userPassword),
        ], $attributes));
    }
}
