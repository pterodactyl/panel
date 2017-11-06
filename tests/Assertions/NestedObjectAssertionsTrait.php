<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Assertions;

use PHPUnit\Framework\Assert;
use PHPUnit_Util_InvalidArgumentHelper;

trait NestedObjectAssertionsTrait
{
    /**
     * Assert that an object value matches an expected value.
     *
     * @param string $key
     * @param mixed  $expected
     * @param object $object
     */
    public function assertObjectNestedValueEquals(string $key, $expected, $object)
    {
        if (! is_object($object)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(3, 'object');
        }

        Assert::assertEquals($expected, object_get_strict($object, $key, '__TEST_FAILURE'), 'Assert that an object value equals a provided value.');
    }

    /**
     * Assert that an object contains a nested key.
     *
     * @param string $key
     * @param object $object
     */
    public function assertObjectHasNestedAttribute(string $key, $object)
    {
        if (! is_object($object)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'object');
        }

        Assert::assertNotEquals('__TEST_FAILURE', object_get_strict($object, $key, '__TEST_FAILURE'), 'Assert that an object contains a nested key.');
    }
}
