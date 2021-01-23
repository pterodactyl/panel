<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Tests\Assertions;

use PHPUnit\Framework\Assert;

trait CommandAssertionsTrait
{
    /**
     * Assert that an output table contains a value.
     *
     * @param mixed  $string
     * @param string $display
     */
    public function assertTableContains($string, $display)
    {
        Assert::assertRegExp('/\|(\s+)' . preg_quote($string) . '(\s+)\|/', $display, 'Assert that a response table contains a value.');
    }
}
