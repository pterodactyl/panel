<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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

        $this->getFunctionMock('\\Pterodactyl\\Services\\Servers', 'str_random')
            ->expects($this->any())->willReturnCallback(function ($count) {
                return str_pad('', $count, '0');
            });
    }

    /**
     * Test that a valid username is returned and is the correct length.
     */
    public function testShouldReturnAValidUsernameWithASelfGeneratedIdentifier()
    {
        $response = $this->service->generate('testname');

        $this->assertEquals('testna_00000000', $response);
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

        $this->assertEquals('testna_xyz00000', $response);
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

        $this->assertEquals('000000_00000000', $response);
    }

    /**
     * Test that a name consisting entirely of special characters is handled.
     */
    public function testNameOfOnlySpecialCharactersIsHandledProperly()
    {
        $response = $this->service->generate('$%#*#(@#(#*$&#(#!#@');

        $this->assertEquals('000000_00000000', $response);
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
