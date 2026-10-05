<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Traits\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use Pterodactyl\Models\User;

trait RequestMockHelpers
{
    protected Request $request;

    private string $requestMockClass = Request::class;

    /**
     * Set the class to mock for requests.
     */
    public function setRequestMockClass(string $class): void
    {
        $this->requestMockClass = $class;

        $this->buildRequestMock();
    }

    /**
     * Configure the user model that the request should return with.
     */
    public function setRequestUserModel(?User $user = null): void
    {
        $this->request->setUserResolver(fn () => $user);
    }

    /**
     * Generates a new request user model and also returns the generated model.
     */
    public function generateRequestUserModel(array $args = []): User
    {
        /** @var User $user */
        $user = User::factory()->make($args);
        $this->setRequestUserModel($user);

        return $user;
    }

    /**
     * Set a request attribute on the mock object.
     */
    public function setRequestAttribute(string $attribute, mixed $value): void
    {
        $this->request->attributes->set($attribute, $value);
    }

    /**
     * Set the request route name.
     */
    public function setRequestRouteName(string $name): void
    {
        $route = new Route(['GET'], '/', ['as' => $name, 'uses' => fn () => null]);
        $this->request->setRouteResolver(fn () => $route);
    }

    /**
     * Set the bearer token carried by the request's Authorization header.
     */
    public function setRequestBearerToken(?string $token): void
    {
        if ($token === null) {
            $this->request->headers->remove('Authorization');

            return;
        }

        $this->request->headers->set('Authorization', 'Bearer '.$token);
    }

    /**
     * Set the active request object to be a real request instance.
     */
    protected function buildRequestMock(): void
    {
        if (! is_a($this->requestMockClass, Request::class, true)) {
            throw new InvalidArgumentException('Request mock class must be an instance of '.Request::class.' when mocked.');
        }

        $class = $this->requestMockClass;
        $this->request = $class::create('/', 'GET');
    }

    /**
     * Sets the mocked request user. If a user model is not provided, a factory model
     * will be created and returned.
     *
     * @deprecated
     */
    protected function setRequestUser(?User $user = null): User
    {
        $user = $user instanceof User ? $user : User::factory()->make();
        $this->setRequestUserModel($user);

        return $user;
    }
}
