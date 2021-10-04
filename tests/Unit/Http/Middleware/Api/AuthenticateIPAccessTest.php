<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware\Api;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Http\Middleware\Api\AuthenticateIPAccess;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateIPAccessTest extends MiddlewareTestCase
{
    /**
     * Test middleware when there are no IP restrictions.
     */
    public function testWithNoIPRestrictions()
    {
        $model = ApiKey::factory()->make(['allowed_ips' => []]);
        $this->setRequestAttribute('api_key', $model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test middleware works correctly when a valid IP accesses
     * and there is an IP restriction.
     */
    public function testWithValidIP()
    {
        $model = ApiKey::factory()->make(['allowed_ips' => ['127.0.0.1']]);
        $this->setRequestAttribute('api_key', $model);

        $this->request->shouldReceive('ip')->withNoArgs()->once()->andReturn('127.0.0.1');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that a CIDR range can be used.
     */
    public function testValidIPAgainstCIDRRange()
    {
        $model = ApiKey::factory()->make(['allowed_ips' => ['192.168.1.1/28']]);
        $this->setRequestAttribute('api_key', $model);

        $this->request->shouldReceive('ip')->withNoArgs()->once()->andReturn('192.168.1.15');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is thrown when an invalid IP address
     * tries to connect and there is an IP restriction.
     */
    public function testWithInvalidIP()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $model = ApiKey::factory()->make(['allowed_ips' => ['127.0.0.1']]);
        $this->setRequestAttribute('api_key', $model);

        $this->request->shouldReceive('ip')->withNoArgs()->twice()->andReturn('127.0.0.2');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware to be used when testing.
     */
    private function getMiddleware(): AuthenticateIPAccess
    {
        return new AuthenticateIPAccess();
    }
}
