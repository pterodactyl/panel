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

use PHPUnit_Framework_Assert;

trait ControllerAssertionsTrait
{
    /**
     * Assert that a view name equals the passed name.
     *
     * @param string                $name
     * @param \Illuminate\View\View $view
     */
    public function assertViewNameEquals($name, $view)
    {
        PHPUnit_Framework_Assert::assertEquals($name, $view->getName());
    }

    /**
     * Assert that a view name does not equal a provided name.
     *
     * @param string                $name
     * @param \Illuminate\View\View $view
     */
    public function assertViewNameNotEquals($name, $view)
    {
        PHPUnit_Framework_Assert::assertNotEquals($name, $view->getName());
    }

    /**
     * Assert that a view has an attribute passed into it.
     *
     * @param string                $attribute
     * @param \Illuminate\View\View $view
     */
    public function assertViewHasKey($attribute, $view)
    {
        PHPUnit_Framework_Assert::assertArrayHasKey($attribute, $view->getData());
    }

    /**
     * Assert that a view does not have a specific attribute passed in.
     *
     * @param string                $attribute
     * @param \Illuminate\View\View $view
     */
    public function assertViewNotHasKey($attribute, $view)
    {
        PHPUnit_Framework_Assert::assertArrayNotHasKey($attribute, $view->getData());
    }

    /**
     * Assert that a view attribute equals a given parameter.
     *
     * @param string                $attribute
     * @param mixed                 $value
     * @param \Illuminate\View\View $view
     */
    public function assertViewKeyEquals($attribute, $value, $view)
    {
        PHPUnit_Framework_Assert::assertEquals($value, array_get($view->getData(), $attribute));
    }

    /**
     * @param string $route
     * @param \Illuminate\Http\RedirectResponse $response
     */
    public function assertRouteRedirectEquals($route, $response)
    {
        PHPUnit_Framework_Assert::assertEquals(route($route), $response->getTargetUrl());
    }
}
