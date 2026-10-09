<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Middleware\MaintenanceMiddlewareTest;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Pterodactyl\Http\Middleware\MaintenanceMiddleware;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Support\Fakes\FakeResponseFactory;

use function pterodactylTestCase;

uses(\Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase::class);
beforeEach(function () {
    $this->factory = new FakeResponseFactory();
    $this->app->instance(ResponseFactory::class, $this->factory);
});
test('continues requests when the node is available', function () {
    $server = Server::factory()->make();
    $node = Node::factory()->make(['maintenance_mode' => false]);
    $server->setRelation('node', $node);
    $this->setRequestAttribute('server', $server);

    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
test('renders the maintenance response when the node is unavailable', function () {
    $server = Server::factory()->make();
    $node = Node::factory()->make(['maintenance_mode' => true]);
    $server->setRelation('node', $node);
    $this->setRequestAttribute('server', $server);

    $response = getMiddleware()->handle($this->request, $this->getClosureAssertions());

    expect($response)->toBeInstanceOf(Response::class);
    expect($this->factory->views)->toBe(['errors.maintenance']);
});
test('rejects requests without a server attribute', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The maintenance middleware requires a server request attribute.');

    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
test('rejects servers with an invalid node relation', function () {
    $server = Server::factory()->make();
    $server->setRelation('node', null);
    $this->setRequestAttribute('server', $server);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The maintenance middleware requires the server node relation.');

    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
function getMiddleware(): MaintenanceMiddleware
{
    return (function () {
        return new MaintenanceMiddleware();
    })->call(pterodactylTestCase());
}
