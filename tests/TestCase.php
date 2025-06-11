<?php

namespace Pterodactyl\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
        CarbonImmutable::setTestNow(Carbon::now());

        // Why, you ask? If we don't force this to false it is possible for certain exceptions
        // to show their error message properly in the integration test output, but not actually
        // be setup correctly to display their message in production.
        //
        // If we expect a message in a test, and it isn't showing up (rather, showing the generic
        // "an error occurred" message), we can probably assume that the exception isn't one that
        // is recognized as being user viewable.
        config()->set('app.debug', false);

        $this->setKnownUuidFactory();
    }

    /**
     * Tear down tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }

    /**
     * Handles the known UUID handling in certain unit tests. Use the "KnownUuid" trait
     * in order to enable this ability.
     */
    public function setKnownUuidFactory()
    {
        // do nothing
    }
}
