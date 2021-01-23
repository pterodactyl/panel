<?php

namespace Pterodactyl\Tests\Assertions;

use PHPUnit\Framework\Assert;

trait MiddlewareAttributeAssertionsTrait
{
    /**
     * Assert a request has an attribute assigned to it.
     */
    public function assertRequestHasAttribute(string $attribute)
    {
        Assert::assertTrue($this->request->attributes->has($attribute), 'Assert that request mock has ' . $attribute . ' attribute.');
    }

    /**
     * Assert a request does not have an attribute assigned to it.
     */
    public function assertRequestMissingAttribute(string $attribute)
    {
        Assert::assertFalse($this->request->attributes->has($attribute), 'Assert that request mock does not have ' . $attribute . ' attribute.');
    }

    /**
     * Assert a request attribute matches an expected value.
     *
     * @param mixed $expected
     */
    public function assertRequestAttributeEquals($expected, string $attribute)
    {
        Assert::assertEquals($expected, $this->request->attributes->get($attribute));
    }
}
