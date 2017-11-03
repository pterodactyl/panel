<?php

namespace Tests\Traits\Http;

use Mockery as m;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\ParameterBag;

trait RequestMockHelpers
{
    /**
     * @var string
     */
    private $requestMockClass = Request::class;

    /**
     * @var \Illuminate\Http\Request|\Mockery\Mock
     */
    protected $request;

    /**
     * Set the class to mock for requests.
     *
     * @param string $class
     */
    public function setRequestMockClass(string $class)
    {
        $this->requestMockClass = $class;

        $this->buildRequestMock();
    }

    /**
     * Set the active request object to be an instance of a mocked request.
     */
    protected function buildRequestMock()
    {
        $this->request = m::mock($this->requestMockClass);
        if (! $this->request instanceof Request) {
            throw new InvalidArgumentException('First argument passed to buildRequestMock must be an instance of \Illuminate\Http\Request when mocked.');
        }

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
