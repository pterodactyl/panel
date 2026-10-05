<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Helpers\EnvironmentWriterTraitTest;

use Pterodactyl\Tests\TestCase;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

uses(TestCase::class);
dataset('variableDataProvider', function () {
    return [['foo', 'foo'], ['abc123', 'abc123'], ['val"ue', '"val\"ue"'], ['my test value', '"my test value"'], ['mysql_p@assword', '"mysql_p@assword"'], ['mysql_p#assword', '"mysql_p#assword"'], ['mysql p@$$word', '"mysql p@$$word"'], ['mysql p%word', '"mysql p%word"'], ['mysql p#word', '"mysql p#word"'], ['abc_@#test', '"abc_@#test"'], ['test 123 $$$', '"test 123 $$$"'], ['#password%', '"#password%"'], ['$pass ', '"$pass "']];
});
test('variable is escaped properly', function ($input, $expected) {
    $output = escapeEnvironmentValue($input);
    expect($output)->toBe($expected);
})->with('variableDataProvider');
function escapeEnvironmentValue(string $value): string
{
    $writer = new class
    {
        use EnvironmentWriterTrait;
    };

    return $writer->escapeEnvironmentValue($value);
}
