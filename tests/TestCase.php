<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->setKnownUuidFactory();
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
