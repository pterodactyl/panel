<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoUnsafeDictionaryProperty;

final class Store
{
    /** @var array<string, mixed> */
    public array $meta = []; // error: line 10

    /** @var array<string, object> */
    private array $bag = []; // error: line 13

    public \stdClass $blob; // error: line 15

    /** @var array<string, string> */
    public array $safe = [];

    /** @var array{id: int} */
    public array $shape = ['id' => 1];

    public function touch(): void
    {
        $this->bag = [];
        $this->blob = new \stdClass;
    }
}
