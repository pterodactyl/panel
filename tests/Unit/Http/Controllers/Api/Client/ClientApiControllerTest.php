<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Http\Controllers\Api\Client\ClientApiControllerTest;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('includes are parsed from a comma separated query value', function () {
    $this->app->instance(Request::class, Request::create('/?include=server%2C+allocations'));
    expect(parsedIncludes())->toBe(['server', 'allocations']);
});
test('includes are parsed from an array query value', function () {
    $this->app->instance(Request::class, Request::create('/?include[]=server&include[]=allocations'));
    expect(parsedIncludes())->toBe(['server', 'allocations']);
});
test('a missing include query value yields no includes', function () {
    $this->app->instance(Request::class, Request::create('/'));
    expect(parsedIncludes())->toBe([]);
});
/** @return list<string> */
function parsedIncludes(): array
{
    $controller = new class extends ClientApiController
    {
        /** @return list<string> */
        public function parsedIncludes(): array
        {
            return $this->parseIncludes();
        }
    };

    return $controller->parsedIncludes();
}
