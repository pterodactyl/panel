<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Helpers;

use Tests\TestCase;

class HumanReadableTest extends TestCase
{
    /**
     * Test the human_readable helper.
     *
     * @dataProvider helperDataProvider
     */
    public function testHelper($value, $response, $precision = 2)
    {
        $this->assertSame($response, human_readable($value, $precision));
    }

    /**
     * Provide data to test against the helper function.
     *
     * @return array
     */
    public function helperDataProvider()
    {
        return [
            [0, '0B'],
            [1, '1B'],
            [1024, '1kB'],
            [10392, '10.15kB'],
            [10392, '10kB', 0],
            [10392, '10.148438kB', 6],
            [1024000, '0.98MB'],
            [1024000, '1MB', 0],
            [102400000, '97.66MB'],
            [102400000, '98MB', 0],
            [102400000, '97.6563MB', 4],
            [102400000, '97.65625MB', 10],
        ];
    }
}
