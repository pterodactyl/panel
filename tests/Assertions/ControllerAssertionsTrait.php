<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Assertions;

use Illuminate\View\View;
use Illuminate\Http\Response;
use PHPUnit_Framework_Assert;
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
        PHPUnit_Framework_Assert::assertInstanceOf(View::class, $response);
    }

    /**
     * Assert that a response is an instance of Illuminate Redirect Response.
     *
     * @param mixed $response
     */
    public function assertIsRedirectResponse($response)
    {
        PHPUnit_Framework_Assert::assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * Assert that a response is an instance of Illuminate Json Response.
     *
     * @param mixed $response
     */
    public function assertIsJsonResponse($response)
    {
        PHPUnit_Framework_Assert::assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * Assert that a response is an instance of Illuminate Response.
     *
     * @param mixed $response
     */
    public function assertIsResponse($response)
    {
        PHPUnit_Framework_Assert::assertInstanceOf(Response::class, $response);
    }

    /**
     * Assert that a view name equals the passed name.
     *
     * @param string $name
     * @param mixed  $view
     */
    public function assertViewNameEquals($name, $view)
    {
        PHPUnit_Framework_Assert::assertEquals($name, $view->getName());
    }

    /**
     * Assert that a view name does not equal a provided name.
     *
     * @param string $name
     * @param mixed  $view
     */
    public function assertViewNameNotEquals($name, $view)
    {
        PHPUnit_Framework_Assert::assertNotEquals($name, $view->getName());
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
            PHPUnit_Framework_Assert::assertNotEquals(
                '__TEST__FAIL',
                array_get($view->getData(), $attribute, '__TEST__FAIL')
            );
        } else {
            PHPUnit_Framework_Assert::assertArrayHasKey($attribute, $view->getData());
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
            PHPUnit_Framework_Assert::assertEquals(
                '__TEST__PASS',
                array_get($view->getData(), $attribute, '__TEST__PASS')
            );
        } else {
            PHPUnit_Framework_Assert::assertArrayNotHasKey($attribute, $view->getData());
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
        PHPUnit_Framework_Assert::assertEquals($value, array_get($view->getData(), $attribute, '__TEST__FAIL'));
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
        PHPUnit_Framework_Assert::assertNotEquals($value, array_get($view->getData(), $attribute, '__TEST__FAIL'));
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
        PHPUnit_Framework_Assert::assertEquals(route($route, $args), $response->getTargetUrl());
    }

    /**
     * Assert that a route redirect URL equals as passed URL.
     *
     * @param string $url
     * @param mixed  $response
     */
    public function assertRedirectUrlEquals($url, $response)
    {
        PHPUnit_Framework_Assert::assertEquals($url, $response->getTargetUrl());
    }

    /**
     * Assert that a response code equals a given code.
     *
     * @param int   $code
     * @param mixed $response
     */
    public function assertResponseCodeEquals($code, $response)
    {
        PHPUnit_Framework_Assert::assertEquals($code, $response->getStatusCode());
    }

    /**
     * Assert that a response code does not equal a given code.
     *
     * @param int   $code
     * @param mixed $response
     */
    public function assertResponseCodeNotEquals($code, $response)
    {
        PHPUnit_Framework_Assert::assertNotEquals($code, $response->getStatusCode());
    }

    /**
     * Assert that a response is in a JSON format.
     *
     * @param mixed $response
     */
    public function assertResponseHasJsonHeaders($response)
    {
        PHPUnit_Framework_Assert::assertEquals('application/json', $response->headers->get('content-type'));
    }

    /**
     * Assert that response JSON matches a given JSON string.
     *
     * @param array|string $json
     * @param mixed        $response
     */
    public function assertResponseJsonEquals($json, $response)
    {
        PHPUnit_Framework_Assert::assertEquals(is_array($json) ? json_encode($json) : $json, $response->getContent());
    }
}
