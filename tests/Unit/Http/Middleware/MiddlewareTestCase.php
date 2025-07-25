<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Pterodactyl\Tests\TestCase;
use Pterodactyl\Tests\Traits\Http\RequestMockHelpers;
use Pterodactyl\Tests\Traits\Http\MocksMiddlewareClosure;
use Pterodactyl\Tests\Assertions\MiddlewareAttributeAssertionsTrait;

abstract class MiddlewareTestCase extends TestCase
{
    use MiddlewareAttributeAssertionsTrait;
    use MocksMiddlewareClosure;
    use RequestMockHelpers;

    /**
     * Setup tests with a mocked request object and normal attributes.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->buildRequestMock();
    }
}
