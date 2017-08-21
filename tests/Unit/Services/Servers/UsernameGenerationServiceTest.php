<?php
/**
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

namespace Tests\Unit\Services\Servers;

use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Services\Servers\UsernameGenerationService;

class UsernameGenerationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var UsernameGenerationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->service = new UsernameGenerationService();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Servers', 'bin2hex')
            ->expects($this->any())->willReturn('dddddddd');

        $this->getFunctionMock('\\Pterodactyl\\Services\\Servers', 'str_random')
            ->expects($this->any())->willReturnCallback(function ($count) {
                return str_pad('', $count, 'a');
            });
    }

    /**
     * Test that a valid username is returned and is the correct length.
     */
    public function testShouldReturnAValidUsernameWithASelfGeneratedIdentifier()
    {
        $response = $this->service->generate('testname');

        $this->assertEquals('testna_dddddddd', $response);
    }

    /**
     * Test that a name and identifier provided returns the expected username.
     */
    public function testShouldReturnAValidUsernameWithAnIdentifierProvided()
    {
        $response = $this->service->generate('testname', 'identifier');

        $this->assertEquals('testna_identifi', $response);
    }

    /**
     * Test that the identifier is extended to 8 characters if it is shorter.
     */
    public function testShouldExtendIdentifierToBe8CharactersIfItIsShorter()
    {
        $response = $this->service->generate('testname', 'xyz');

        $this->assertEquals('testna_xyzaaaaa', $response);
    }

    /**
     * Test that special characters are removed from the username.
     */
    public function testShouldStripSpecialCharactersFromName()
    {
        $response = $this->service->generate('te!st_n$ame', 'identifier');

        $this->assertEquals('testna_identifi', $response);
    }

    /**
     * Test that an empty name is replaced with 6 random characters.
     */
    public function testEmptyNamesShouldBeReplacedWithRandomCharacters()
    {
        $response = $this->service->generate('');

        $this->assertEquals('aaaaaa_dddddddd', $response);
    }

    /**
     * Test that a name consisting entirely of special characters is handled.
     */
    public function testNameOfOnlySpecialCharactersIsHandledProperly()
    {
        $response = $this->service->generate('$%#*#(@#(#*$&#(#!#@');

        $this->assertEquals('aaaaaa_dddddddd', $response);
    }

    /**
     * Test that passing a name shorter than 6 characters returns the entire name.
     */
    public function testNameShorterThan6CharactersShouldBeRenderedEntirely()
    {
        $response = $this->service->generate('test', 'identifier');

        $this->assertEquals('test_identifi', $response);
    }
}
