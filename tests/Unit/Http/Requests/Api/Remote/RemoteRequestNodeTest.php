<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Requests\Api\Remote\RemoteRequestNodeTest;

use Illuminate\Http\Request;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\TestCase;
use UnexpectedValueException;

uses(TestCase::class);
test('returns the authenticated node request attribute', function () {
    $request = Request::create('/');
    $node = new Node();
    $request->attributes->set('node', $node);
    expect(RemoteRequestNode::get($request))->toBe($node);
});
test('rejects requests without an authenticated node', function () {
    $this->expectException(UnexpectedValueException::class);
    RemoteRequestNode::get(Request::create('/'));
});
