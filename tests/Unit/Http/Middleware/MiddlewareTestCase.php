<?php

namespace Tests\Unit\Http\Middleware;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Tests\Traits\Http\MocksMiddlewareClosure;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\Assertions\MiddlewareAttributeAssertionsTrait;

abstract class MiddlewareTestCase extends TestCase
{
    use MiddlewareAttributeAssertionsTrait, MocksMiddlewareClosure;

    /**
     * @var \Illuminate\Http\Request|\Mockery\Mock
     */
    protected $request;

    /**
     * Setup tests with a mocked request object and normal attributes.
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = m::mock(Request::class);
        $this->request->attributes = new ParameterBag();
    }

    /**
     * Set a request attribute on the mock object.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    protected function setRequestAttribute(string $attribute, $value)
    {
        $this->request->attributes->set($attribute, $value);
    }

    /**
     * Sets the mocked request user. If a user model is not provided, a factory model
     * will be created and returned.
     *
     * @param \Pterodactyl\Models\User|null $user
     * @return \Pterodactyl\Models\User
     */
    protected function setRequestUser(User $user = null): User
    {
        $user = $user instanceof User ? $user : factory(User::class)->make();
        $this->request->shouldReceive('user')->withNoArgs()->andReturn($user);

        return $user;
    }
}
