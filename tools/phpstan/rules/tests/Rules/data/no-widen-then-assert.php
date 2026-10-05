<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoWidenThenAssert;

final class Payload
{
    public function __construct(public readonly string $id) {}
}

function widenViaCast(Payload $payload): string
{
    $widened = (array) $payload;

    /** @var array{id: string} $widened */
    $widened; // error: line 17

    return $widened['id'];
}

function widenViaJsonRoundTrip(Payload $payload): int
{
    $data = json_decode(json_encode($payload));

    assert(is_object($data)); // error: line 25

    return 1;
}

function widenViaObjectVars(Payload $payload): int
{
    $vars = get_object_vars($payload);

    $id = (string) $vars; // error: line 34 — cast recreates evidence the cast erased

    return strlen($id);
}

function widenedButReassigned(Payload $payload): string
{
    $value = (array) $payload;
    $value = ['id' => 'fresh'];

    /** @var array{id: string} $value */
    $value;

    return $value['id'];
}

function neverWidened(Payload $payload): string
{
    $direct = $payload->id;

    return (string) $direct;
}
