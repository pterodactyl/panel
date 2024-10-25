<?php

namespace Facebook\WebDriver;

/**
 * WebDriver interface implemented by drivers that support JavaScript.
 */
interface JavaScriptExecutor
{
    /**
     * Inject a snippet of JavaScript into the page for execution in the context
     * of the currently selected frame. The executed script is assumed to be
     * synchronous and the result of evaluating the script will be returned.
     *
     * @param string $script The script to inject.
     * @param array $arguments The arguments of the script.
     * @return mixed The return value of the script.
     */
    public function executeScript($script, array $arguments = []);

    /**
     * Inject a snippet of JavaScript into the page for asynchronous execution in
     * the context of the currently selected frame.
     *
     * The driver will pass a callback as the last argument to the snippet, and
     * block until the callback is invoked.
     *
     * @see WebDriverExecuteAsyncScriptTestCase
     *
     * @param string $script The script to inject.
     * @param array $arguments The arguments of the script.
     * @return mixed The value passed by the script to the callback.
     */
    public function executeAsyncScript($script, array $arguments = []);
}
