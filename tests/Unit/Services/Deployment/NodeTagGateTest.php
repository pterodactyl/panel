<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Deployment\NodeTagGateTest;

use Pterodactyl\Services\Deployment\NodeTagGate;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
beforeEach(function () {
    $this->gate = new NodeTagGate();
});
test('untagged node accepts any egg', function () {
    expect($this->gate->allows([], [], ['rust']))->toBeTrue();
    expect($this->gate->allows([], [], []))->toBeTrue();
});
test('game match is or', function () {
    $node = ['rust', 'minecraft'];
    expect($this->gate->allows($node, [], ['rust']))->toBeTrue();
    expect($this->gate->allows($node, [], ['minecraft']))->toBeTrue();
    expect($this->gate->allows($node, [], ['minecraft', 'bedrock']))->toBeTrue();
    expect($this->gate->allows($node, [], ['garrysmod']))->toBeFalse();
});
test('untagged egg only lands on untagged node', function () {
    expect($this->gate->allows(['rust'], [], []))->toBeFalse();
    expect($this->gate->allows([], [], []))->toBeTrue();
});
test('reservation blocks unoped deploy', function () {
    expect($this->gate->allows(['rust'], ['premium'], ['rust'], []))->toBeFalse();
    expect($this->gate->allows(['rust'], ['premium'], ['rust'], ['premium']))->toBeTrue();
});
test('targeted deploy requires every tag on the node', function () {
    expect($this->gate->allows(['rust'], [], ['rust'], ['premium']))->toBeFalse();
    expect($this->gate->allows(['rust'], ['premium'], ['rust'], ['premium', 'eu']))->toBeFalse();
    expect($this->gate->allows(['rust'], ['premium', 'eu'], ['rust'], ['premium', 'eu']))->toBeTrue();
});
test('node with no games but a reservation', function () {
    expect($this->gate->allows([], ['premium'], ['rust'], ['premium']))->toBeTrue();
    expect($this->gate->allows([], ['premium'], ['minecraft'], ['premium']))->toBeTrue();
    // ...but an untargeted deploy still cannot claim a reserved node.
    expect($this->gate->allows([], ['premium'], ['rust'], []))->toBeFalse();
});
test('reservation does not leak through the game or', function () {
    expect($this->gate->allows(['rust', 'minecraft'], ['premium'], ['minecraft'], ['budget']))->toBeFalse();
    expect($this->gate->allows(['rust', 'minecraft'], ['premium'], ['minecraft'], ['premium']))->toBeTrue();
});
