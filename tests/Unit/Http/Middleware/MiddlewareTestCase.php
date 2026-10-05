<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Unit\Http\Middleware;

use Pterodactyl\Tests\Assertions\MiddlewareAttributeAssertionsTrait;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Tests\Traits\Http\MocksMiddlewareClosure;
use Pterodactyl\Tests\Traits\Http\RequestMockHelpers;

abstract class MiddlewareTestCase extends TestCase
{
    use MiddlewareAttributeAssertionsTrait;
    use MocksMiddlewareClosure;
    use RequestMockHelpers;

    /**
     * Setup tests with a mocked request object and normal attributes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildRequestMock();
    }
}
