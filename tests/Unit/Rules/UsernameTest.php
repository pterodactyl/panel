<?php

namespace Pterodactyl\Tests\Unit\Rules;

use Pterodactyl\Rules\Username;
use Pterodactyl\Tests\TestCase;

class UsernameTest extends TestCase
{
    /**
     * Test that this rule can be cast to a string correctly.
     */
    public function testRuleIsStringable()
    {
        $this->assertSame('p_username', (string) new Username());
    }

    /**
     * Test valid usernames.
     *
     * @dataProvider validUsernameDataProvider
     */
    public function testValidUsernames(string $username)
    {
        $this->assertTrue((new Username())->passes('test', $username), 'Assert username is valid.');
    }

    /**
     * Test invalid usernames return false.
     *
     * @dataProvider invalidUsernameDataProvider
     */
    public function testInvalidUsernames(string $username)
    {
        $this->assertFalse((new Username())->passes('test', $username), 'Assert username is not valid.');
    }

    /**
     * Provide valid usernames.
     */
    public static function validUsernameDataProvider(): array
    {
        return [
            ['username'],
            ['user_name'],
            ['user.name'],
            ['user-name'],
            ['123username123'],
            ['123-user.name'],
            ['123456'],
        ];
    }

    /**
     * Provide invalid usernames.
     */
    public static function invalidUsernameDataProvider(): array
    {
        return [
            ['_username'],
            ['username_'],
            ['_username_'],
            ['-username'],
            ['.username'],
            ['username-'],
            ['username.'],
            ['user*name'],
            ['user^name'],
            ['user#name'],
            ['user+name'],
            ['1234_'],
        ];
    }
}
