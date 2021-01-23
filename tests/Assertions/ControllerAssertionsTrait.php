<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Tests\Assertions;

use Illuminate\View\View;
use Illuminate\Http\Response;
use PHPUnit\Framework\Assert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait ControllerAssertionsTrait
{
    /**
     * Assert that a response is an instance of Illuminate View.
     *
     * @param mixed $response
     */
    public function assertIsViewResponse($response)
    {
        Assert::assertInstanceOf(View::class, $response);
    }

    /**
     * Assert that a response is an instance of Illuminate Redirect Response.
     *
     * @param mixed $response
     */
    public function assertIsRedirectResponse($response)
    {
        Assert::assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * Assert that a response is an instance of Illuminate Json Response.
     *
     * @param mixed $response
     */
    public function assertIsJsonResponse($response)
    {
        Assert::assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * Assert that a response is an instance of Illuminate Response.
     *
     * @param mixed $response
     */
    public function assertIsResponse($response)
    {
        Assert::assertInstanceOf(Response::class, $response);
    }

    /**
     * Assert that a view name equals the passed name.
     *
     * @param string $name
     * @param mixed  $view
     */
    public function assertViewNameEquals($name, $view)
    {
        Assert::assertEquals($name, $view->getName());
    }

    /**
     * Assert that a view name does not equal a provided name.
     *
     * @param string $name
     * @param mixed  $view
     */
    public function assertViewNameNotEquals($name, $view)
    {
        Assert::assertNotEquals($name, $view->getName());
    }

    /**
     * Assert that a view has an attribute passed into it.
     *
     * @param string $attribute
     * @param mixed  $view
     */
    public function assertViewHasKey($attribute, $view)
    {
        if (str_contains($attribute, '.')) {
            Assert::assertNotEquals(
                '__TEST__FAIL',
                array_get($view->getData(), $attribute, '__TEST__FAIL')
            );
        } else {
            Assert::assertArrayHasKey($attribute, $view->getData());
        }
    }

    /**
     * Assert that a view does not have a specific attribute passed in.
     *
     * @param string $attribute
     * @param mixed  $view
     */
    public function assertViewNotHasKey($attribute, $view)
    {
        if (str_contains($attribute, '.')) {
            Assert::assertEquals(
                '__TEST__PASS',
                array_get($view->getData(), $attribute, '__TEST__PASS')
            );
        } else {
            Assert::assertArrayNotHasKey($attribute, $view->getData());
        }
    }

    /**
     * Assert that a view attribute equals a given parameter.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param mixed  $view
     */
    public function assertViewKeyEquals($attribute, $value, $view)
    {
        Assert::assertEquals($value, array_get($view->getData(), $attribute, '__TEST__FAIL'));
    }

    /**
     * Assert that a view attribute does not equal a given parameter.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param mixed  $view
     */
    public function assertViewKeyNotEquals($attribute, $value, $view)
    {
        Assert::assertNotEquals($value, array_get($view->getData(), $attribute, '__TEST__FAIL'));
    }

    /**
     * Assert that a route redirect equals a given route name.
     *
     * @param string $route
     * @param mixed  $response
     * @param array  $args
     */
    public function assertRedirectRouteEquals($route, $response, array $args = [])
    {
        Assert::assertEquals(route($route, $args), $response->getTargetUrl());
    }

    /**
     * Assert that a route redirect URL equals as passed URL.
     *
     * @param string $url
     * @param mixed  $response
     */
    public function assertRedirectUrlEquals($url, $response)
    {
        Assert::assertEquals($url, $response->getTargetUrl());
    }

    /**
     * Assert that a response code equals a given code.
     *
     * @param int   $code
     * @param mixed $response
     */
    public function assertResponseCodeEquals($code, $response)
    {
        Assert::assertEquals($code, $response->getStatusCode());
    }

    /**
     * Assert that a response code does not equal a given code.
     *
     * @param int   $code
     * @param mixed $response
     */
    public function assertResponseCodeNotEquals($code, $response)
    {
        Assert::assertNotEquals($code, $response->getStatusCode());
    }

    /**
     * Assert that a response is in a JSON format.
     *
     * @param mixed $response
     */
    public function assertResponseHasJsonHeaders($response)
    {
        Assert::assertEquals('application/json', $response->headers->get('content-type'));
    }

    /**
     * Assert that response JSON matches a given JSON string.
     *
     * @param array|string $json
     * @param mixed        $response
     */
    public function assertResponseJsonEquals($json, $response)
    {
        Assert::assertEquals(is_array($json) ? json_encode($json) : $json, $response->getContent());
    }
}
