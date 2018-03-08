<?php

namespace Tests;

use Cake\Chronos\Chronos;
use Illuminate\Support\Facades\Hash;
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

        Hash::setRounds(4);
        $this->setKnownUuidFactory();
    }

    /**
     * Tear down tests.
     */
    protected function tearDown()
    {
        parent::tearDown();

        Chronos::setTestNow();
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
