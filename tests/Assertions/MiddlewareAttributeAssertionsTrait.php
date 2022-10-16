<?php

namespace Pterodactyl\Tests\Assertions;

use PHPUnit\Framework\Assert;

trait MiddlewareAttributeAssertionsTrait
{
    /**
     * Assert a request has an attribute assigned to it.
     */
    public function assertRequestHasAttribute(string $attribute): void
    {
        Assert::assertTrue($this->request->attributes->has($attribute), 'Assert that request mock has ' . $attribute . ' attribute.');
    }

    /**
     * Assert a request does not have an attribute assigned to it.
     */
    public function assertRequestMissingAttribute(string $attribute): void
    {
        Assert::assertFalse($this->request->attributes->has($attribute), 'Assert that request mock does not have ' . $attribute . ' attribute.');
    }

    /**
     * Assert a request attribute matches an expected value.
     */
    public function assertRequestAttributeEquals(mixed $expected, string $attribute): void
    {
        Assert::assertEquals($expected, $this->request->attributes->get($attribute));
    }
}
