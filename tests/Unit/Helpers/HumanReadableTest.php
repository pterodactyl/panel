<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     * Provide data to test aganist the helper function.
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
