<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoKnownValueWidening;

final class Registry
{
    /** @var array<string, mixed> */
    public array $handlers = ['start' => 'startHandler']; // error: line 10

    public object $config; // no default: fine here (dictionary rule owns the type itself)

    /** @var array<string, mixed> */
    public array $empty = []; // empty array carries no evidence: fine

    /** @var array{start: string} */
    public array $shaped = ['start' => 'startHandler'];

    public mixed $marker = 'sentinel'; // error: line 20

    public function __construct()
    {
        $this->config = new \stdClass;
    }
}

function widensReturn(): mixed // error: line 28
{
    return ['start' => 'startHandler'];
}

function keepsInference(): array // level 6 owns the missing value type; evidence intact
{
    return ['start' => 'startHandler'];
}

/**
 * @return array{start: string}
 */
function named(): array
{
    return ['start' => 'startHandler'];
}

function conditional(bool $flag): mixed
{
    if ($flag) {
        return ['start' => 'startHandler'];
    }

    return unserialize('b:0;'); // one un-known return: not flagged
}
