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

class IsDigitTest extends TestCase
{
    /**
     * Test the is_digit helper.
     *
     * @dataProvider helperDataProvider
     */
    public function testHelper($value, $response)
    {
        $this->assertSame($response, is_digit($value));
    }

    /**
     * Provide data to test aganist the helper function.
     *
     * @return array
     */
    public function helperDataProvider()
    {
        return [
            [true, false],
            [false, false],
            [12.3, false],
            ['12.3', false],
            ['string', false],
            [-1, false],
            ['-1', false],
            [1, true],
            [0, true],
            [12345, true],
            ['12345', true],
            ['true', false],
            ['false', false],
            ['123_test', false],
            ['123.test', false],
            ['123test', false],
            ['test123', false],
            ['0x00000003', false],
            [00000011, true],
            ['00000011', true],
            ['AD9C', false],
        ];
    }
}
