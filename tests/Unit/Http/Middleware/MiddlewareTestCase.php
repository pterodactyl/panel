<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Tests\Traits\Http\RequestMockHelpers;
use Tests\Traits\Http\MocksMiddlewareClosure;
use Tests\Assertions\MiddlewareAttributeAssertionsTrait;

abstract class MiddlewareTestCase extends TestCase
{
    use MiddlewareAttributeAssertionsTrait, MocksMiddlewareClosure, RequestMockHelpers;

    /**
     * Setup tests with a mocked request object and normal attributes.
     */
    public function setUp()
    {
        parent::setUp();

        $this->buildRequestMock();
    }
}
