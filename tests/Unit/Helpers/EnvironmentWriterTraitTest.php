<?php

namespace Pterodactyl\Tests\Unit\Helpers;

use Pterodactyl\Tests\TestCase;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

class EnvironmentWriterTraitTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('variableDataProvider')]
    public function testVariableIsEscapedProperly($input, $expected)
    {
        $output = (new FooClass())->escapeEnvironmentValue($input);

        $this->assertSame($expected, $output);
    }

    public static function variableDataProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['abc123', 'abc123'],
            ['val"ue', '"val\"ue"'],
            ['my test value', '"my test value"'],
            ['mysql_p@assword', '"mysql_p@assword"'],
            ['mysql_p#assword', '"mysql_p#assword"'],
            ['mysql p@$$word', '"mysql p@$$word"'],
            ['mysql p%word', '"mysql p%word"'],
            ['mysql p#word', '"mysql p#word"'],
            ['abc_@#test', '"abc_@#test"'],
            ['test 123 $$$', '"test 123 $$$"'],
            ['#password%', '"#password%"'],
            ['$pass ', '"$pass "'],
        ];
    }
}

class FooClass
{
    use EnvironmentWriterTrait;
}
