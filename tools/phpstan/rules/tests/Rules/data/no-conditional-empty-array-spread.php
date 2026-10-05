<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoConditionalEmptyArraySpread;

function build(bool $condition, int $value): array
{
    $viaSpread = [
        'base' => 1,
        ...($condition ? ['key' => $value] : []), // error: line 11
    ];

    $viaMerge = array_merge(['base' => 1], $condition ? ['key' => $value] : []); // error: line 14

    $viaReplace = array_replace(['base' => 1], $condition ? [] : ['key' => $value]); // error: line 16

    $viaPlus = ['base' => 1] + ($condition ? ['key' => $value] : []); // error: line 18

    $fineSpread = [...['a' => 1], ...['b' => 2]];
    $fineTernary = $condition ? ['key' => $value] : ['key' => 0];
    $fineMerge = array_merge(['a' => 1], ['b' => 2]);

    return [$viaSpread, $viaMerge, $viaReplace, $viaPlus, $fineSpread, $fineTernary, $fineMerge];
}
