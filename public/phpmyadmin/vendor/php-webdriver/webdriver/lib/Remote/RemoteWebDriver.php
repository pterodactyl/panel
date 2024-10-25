<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Exception\UnknownErrorException;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\Support\IsElementDisplayedAtom;
use Facebook\WebDriver\Support\ScreenshotHelper;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverCapabilities;
use Facebook\WebDriver\WebDriverCommandExecutor;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverHasInputDevices;
use Facebook\WebDriver\WebDriverNavigation;
use Facebook\WebDriver\WebDriverOptions;
use Facebook\WebDriver\WebDriverWait;

class RemoteWebDriver implements WebDriver, JavaScriptExecutor, WebDriverHasInputDevices
{
    /**
     * @var HttpCommandExecutor|null
     */
    protected $executor;
    /**
     * @var WebDriverCapabilities|null
     */
    protected $capabilities;

    /**
     * @var string
     */
    protected $sessionID;
    /**
     * @var RemoteMouse
     */
    protected $mouse;
    /**
     * @var RemoteKeyboard
     */
    protected $keyboard;
    /**
     * @var RemoteTouchScreen
     */
    protected $touch;
    /**
     * @var RemoteExecuteMethod
     */
    protected $executeMethod;
    /**
     * @var bool
     */
    protected $isW3cCompliant;

    /**
     * @param HttpCommandExecutor $commandExecutor
     * @param string $sessionId
     * @param WebDriverCapabilities|null $capabilities
     * @param bool $isW3cCompliant false to use the legacy JsonWire protocol, true for the W3C WebDriver spec
     */
    protected function __construct(
        HttpCommandExecutor $commandExecutor,
        $sessionId,
        WebDriverCapabilities $capabilities = null,
        $isW3cCompliant = false
    ) {
        $this->executor = $commandExecutor;
        $this->sessionID = $sessionId;
        $this->isW3cCompliant = $isW3cCompliant;

        if ($capabilities !== null) {
            $this->capabilities = $capabilities;
        }
    }

    /**
     * Construct the RemoteWebDriver by a desired capabilities.
     *
     * @param string $selenium_server_url The url of the remote Selenium WebDriver server
     * @param DesiredCapabilities|array $desired_capabilities The desired capabilities
     * @param int|null $connection_timeout_in_ms Set timeout for the connect phase to remote Selenium WebDriver server
     * @param int|null $request_timeout_in_ms Set the maximum time of a request to remote Selenium WebDriver server
     * @param string|null $http_proxy The proxy to tunnel requests to the remote Selenium WebDriver through
     * @param int|null $http_proxy_port The proxy port to tunnel requests to the remote Selenium WebDriver through
     * @param DesiredCapabilities $required_capabilities The required capabilities
     *
     * @return static
     */
    public static function create(
        $selenium_server_url = 'http://localhost:4444/wd/hub',
        $desired_capabilities = null,
        $connection_timeout_in_ms = null,
        $request_timeout_in_ms = null,
        $http_proxy = null,
        $http_proxy_port = null,
        DesiredCapabilities $required_capabilities = null
    ) {
        $selenium_server_url = preg_replace('#/+$#', '', $selenium_server_url);

        $desired_capabilities = self::castToDesiredCapabilitiesObject($desired_capabilities);

        $executor = new HttpCommandExecutor($selenium_server_url, $http_proxy, $http_proxy_port);
        if ($connection_timeout_in_ms !== null) {
            $executor->setConnectionTimeout($connection_timeout_in_ms);
        }
        if ($request_timeout_in_ms !== null) {
            $executor->setRequestTimeout($request_timeout_in_ms);
        }

        // W3C
        $parameters = [
            'capabilities' => [
                'firstMatch' => [(object) $desired_capabilities->toW3cCompatibleArray()],
            ],
        ];

        if ($required_capabilities !== null && !empty($required_capabilities->toArray())) {
            $parameters['capabilities']['alwaysMatch'] = (object) $required_capabilities->toW3cCompatibleArray();
        }

        // Legacy protocol
        if ($required_capabilities !== null) {
            // TODO: Selenium (as of v3.0.1) does accept requiredCapabilities only as a property of desiredCapabilities.
            // This has changed with the W3C WebDriver spec, but is the only way how to pass these
            // values with the legacy protocol.
            $desired_capabilities->setCapability('requiredCapabilities', (object) $required_capabilities->toArray());
        }

        $parameters['desiredCapabilities'] = (object) $desired_capabilities->toArray();

        $command = WebDriverCommand::newSession($parameters);

        $response = $executor->execute($command);

        return static::createFromResponse($response, $executor);
    }

    /**
     * [Experimental] Construct the RemoteWebDriver by an existing session.
     *
     * This constructor can boost the performance a lot by reusing the same browser for the whole test suite.
     * You cannot pass the desired capabilities because the session was created before.
     *
     * @param string $session_id The existing session id
     * @param string $selenium_server_url The url of the remote Selenium WebDriver server
     * @param int|null $connection_timeout_in_ms Set timeout for the connect phase to remote Selenium WebDriver server
     * @param int|null $request_timeout_in_ms Set the maximum time of a request to remote Selenium WebDriver server
     * @param bool $isW3cCompliant True to use W3C WebDriver (default), false to use the legacy JsonWire protocol
     * @return static
     */
    public static function createBySessionID(
        $session_id,
        $selenium_server_url = 'http://localhost:4444/wd/hub',
        $connection_timeout_in_ms = null,
        $request_timeout_in_ms = null
    ) {
        // BC layer to not break the method signature
        $isW3cCompliant = func_num_args() > 4 ? func_get_arg(4) : true;

        $executor = new HttpCommandExecutor($selenium_server_url, null, null);
        if ($connection_timeout_in_ms !== null) {
            $executor->setConnectionTimeout($connection_timeout_in_ms);
        }
        if ($request_timeout_in_ms !== null) {
            $executor->setRequestTimeout($request_timeout_in_ms);
        }

        if (!$isW3cCompliant) {
            $executor->disableW3cCompliance();
        }

        return new static($executor, $session_id, null, $isW3cCompliant);
    }

    /**
     * Close the current window.
     *
     * @return RemoteWebDriver The current instance.
     */
    public function close()
    {
        $this->execute(DriverCommand::CLOSE, []);

        return $this;
    }

    /**
     * Create a new top-level browsing context.
     *
     * @codeCoverageIgnore
     * @deprecated Use $driver->switchTo()->newWindow()
     * @return WebDriver The current instance.
     */
    public function newWindow()
    {
        return $this->switchTo()->newWindow();
    }

    /**
     * Find the first WebDriverElement using the given mechanism.
     *
     * @param WebDriverBy $by
     * @return RemoteWebElement NoSuchElementException is thrown in HttpCommandExecutor if no element is found.
     * @see WebDriverBy
     */
    public function findElement(WebDriverBy $by)
    {
        $raw_element = $this->execute(
            DriverCommand::FIND_ELEMENT,
            JsonWireCompat::getUsing($by, $this->isW3cCompliant)
        );

        if ($raw_element === null) {
            throw new UnknownErrorException('Unexpected server response to findElement command');
        }

        return $this->newElement(JsonWireCompat::getElement($raw_element));
    }

    /**
     * Find all WebDriverElements within the current page using the given mechanism.
     *
     * @param WebDriverBy $by
     * @return RemoteWebElement[] A list of all WebDriverElements, or an empty array if nothing matches
     * @see WebDriverBy
     */
    public function findElements(WebDriverBy $by)
    {
        $raw_elements = $this->execute(
            DriverCommand::FIND_ELEMENTS,
            JsonWireCompat::getUsing($by, $this->isW3cCompliant)
        );

        if ($raw_elements === null) {
            throw new UnknownErrorException('Unexpected server response to findElements command');
        }

        $elements = [];
        foreach ($raw_elements as $raw_element) {
            $elements[] = $this->newElement(JsonWireCompat::getElement($raw_element));
        }

        return $elements;
    }

    /**
     * Load a new web page in the current browser window.
     *
     * @param string $url
     *
     * @return RemoteWebDriver The current instance.
     */
    public function get($url)
    {
        $params = ['url' => (string) $url];
        $this->execute(DriverCommand::GET, $params);

        return $this;
    }

    /**
     * Get a string representing the current URL that the browser is looking at.
     *
     * @return string The current URL.
     */
    public function getCurrentURL()
    {
        return $this->execute(DriverCommand::GET_CURRENT_URL);
    }

    /**
     * Get the source of the last loaded page.
     *
     * @return string The current page source.
     */
    public function getPageSource()
    {
        return $this->execute(DriverCommand::GET_PAGE_SOURCE);
    }

    /**
     * Get the title of the current page.
     *
     * @return string The title of the current page.
     */
    public function getTitle()
    {
        return $this->execute(DriverCommand::GET_TITLE);
    }

    /**
     * Return an opaque handle to this window that uniquely identifies it within this driver instance.
     *
     * @return string The current window handle.
     */
    public function getWindowHandle()
    {
        return $this->execute(
            DriverCommand::GET_CURRENT_WINDOW_HANDLE,
            []
        );
    }

    /**
     * Get all window handles available to the current session.
     *
     * Note: Do not use `end($driver->getWindowHandles())` to find the last open window, for proper solution see:
     * https://github.com/php-webdriver/php-webdriver/wiki/Alert,-tabs,-frames,-iframes#switch-to-the-new-window
     *
     * @return array An array of string containing all available window handles.
     */
    public function getWindowHandles()
    {
        return $this->execute(DriverCommand::GET_WINDOW_HANDLES, []);
    }

    /**
     * Quits this driver, closing every associated window.
     */
    public function quit()
    {
        $this->execute(DriverCommand::QUIT);
        $this->executor = null;
    }

    /**
     * Inject a snippet of JavaScript into the page for execution in the context of the currently selected frame.
     * The executed script is assumed to be synchronous and the result of evaluating the script will be returned.
     *
     * @param string $script The script to inject.
     * @param array $arguments The arguments of the script.
     * @return mixed The return value of the script.
     */
    public function executeScript($script, array $arguments = [])
    {
        $params = [
            'script' => $script,
            'args' => $this->prepareScriptArguments($arguments),
        ];

        return $this->execute(DriverCommand::EXECUTE_SCRIPT, $params);
    }

    /**
     * Inject a snippet of JavaScript into the page for asynchronous execution in the context of the currently selected
     * frame.
     *
     * The driver will pass a callback as the last argument to the snippet, and block until the callback is invoked.
     *
     * You may need to define script timeout using `setScriptTimeout()` method of `WebDriverTimeouts` first.
     *
     * @param string $script The script to inject.
     * @param array $arguments The arguments of the script.
     * @return mixed The value passed by the script to the callback.
     */
    public function executeAsyncScript($script, array $arguments = [])
    {
        $params = [
            'script' => $script,
            'args' => $this->prepareScriptArguments($arguments),
        ];

        return $this->execute(
            DriverCommand::EXECUTE_ASYNC_SCRIPT,
            $params
        );
    }

    /**
     * Take a screenshot of the current page.
     *
     * @param string $save_as The path of the screenshot to be saved.
     * @return string The screenshot in PNG format.
     */
    public function takeScreenshot($save_as = null)
    {
        return (new ScreenshotHelper($this->getExecuteMethod()))->takePageScreenshot($save_as);
    }

    /**
     * Status returns information about whether a remote end is in a state in which it can create new sessions.
     */
    public function getStatus()
    {
        $response = $this->execute(DriverCommand::STATUS);

        return RemoteStatus::createFromResponse($response);
    }

    /**
     * Construct a new WebDriverWait by the current WebDriver instance.
     * Sample usage:
     *
     * ```
     *   $driver->wait(20, 1000)->until(
     *     WebDriverExpectedCondition::titleIs('WebDriver Page')
     *   );
     * ```
     * @param int $timeout_in_second
     * @param int $interval_in_millisecond
     *
     * @return WebDriverWait
     */
    public function wait($timeout_in_second = 30, $interval_in_millisecond = 250)
    {
        return new WebDriverWait(
            $this,
            $timeout_in_second,
            $interval_in_millisecond
        );
    }

    /**
     * An abstraction for managing stuff you would do in a browser menu. For example, adding and deleting cookies.
     *
     * @return WebDriverOptions
     */
    public function manage()
    {
        return new WebDriverOptions($this->getExecuteMethod(), $this->isW3cCompliant);
    }

    /**
     * An abstraction allowing the driver to access the browser's history and to navigate to a given URL.
     *
     * @return WebDriverNavigation
     * @see WebDriverNavigation
     */
    public function navigate()
    {
        return new WebDriverNavigation($this->getExecuteMethod());
    }

    /**
     * Switch to a different window or frame.
     *
     * @return RemoteTargetLocator
     * @see RemoteTargetLocator
     */
    public function switchTo()
    {
        return new RemoteTargetLocator($this->getExecuteMethod(), $this, $this->isW3cCompliant);
    }

    /**
     * @return RemoteMouse
     */
    public function getMouse()
    {
        if (!$this->mouse) {
            $this->mouse = new RemoteMouse($this->getExecuteMethod(), $this->isW3cCompliant);
        }

        return $this->mouse;
    }

    /**
     * @return RemoteKeyboard
     */
    public function getKeyboard()
    {
        if (!$this->keyboard) {
            $this->keyboard = new RemoteKeyboard($this->getExecuteMethod(), $this, $this->isW3cCompliant);
        }

        return $this->keyboard;
    }

    /**
     * @return RemoteTouchScreen
     */
    public function getTouch()
    {
        if (!$this->touch) {
            $this->touch = new RemoteTouchScreen($this->getExecuteMethod());
        }

        return $this->touch;
    }

    /**
     * Construct a new action builder.
     *
     * @return WebDriverActions
     */
    public function action()
    {
        return new WebDriverActions($this);
    }

    /**
     * Set the command executor of this RemoteWebdriver
     *
     * @deprecated To be removed in the future. Executor should be passed in the constructor.
     * @internal
     * @codeCoverageIgnore
     * @param WebDriverCommandExecutor $executor Despite the typehint, it have be an instance of HttpCommandExecutor.
     * @return RemoteWebDriver
     */
    public function setCommandExecutor(WebDriverCommandExecutor $executor)
    {
        $this->executor = $executor;

        return $this;
    }

    /**
     * Get the command executor of this RemoteWebdriver
     *
     * @return HttpCommandExecutor
     */
    public function getCommandExecutor()
    {
        return $this->executor;
    }

    /**
     * Set the session id of the RemoteWebDriver.
     *
     * @deprecated To be removed in the future. Session ID should be passed in the constructor.
     * @internal
     * @codeCoverageIgnore
     * @param string $session_id
     * @return RemoteWebDriver
     */
    public function setSessionID($session_id)
    {
        $this->sessionID = $session_id;

        return $this;
    }

    /**
     * Get current selenium sessionID
     *
     * @return string
     */
    public function getSessionID()
    {
        return $this->sessionID;
    }

    /**
     * Get capabilities of the RemoteWebDriver.
     *
     * @return WebDriverCapabilities|null
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * Returns a list of the currently active sessions.
     *
     * @deprecated Removed in W3C WebDriver.
     * @param string $selenium_server_url The url of the remote Selenium WebDriver server
     * @param int $timeout_in_ms
     * @return array
     */
    public static function getAllSessions($selenium_server_url = 'http://localhost:4444/wd/hub', $timeout_in_ms = 30000)
    {
        $executor = new HttpCommandExecutor($selenium_server_url, null, null);
        $executor->setConnectionTimeout($timeout_in_ms);

        $command = new WebDriverCommand(
            null,
            DriverCommand::GET_ALL_SESSIONS,
            []
        );

        return $executor->execute($command)->getValue();
    }

    public function execute($command_name, $params = [])
    {
        // As we so far only use atom for IS_ELEMENT_DISPLAYED, this condition is hardcoded here. In case more atoms
        // are used, this should be rewritten and separated from this class (e.g. to some abstract matcher logic).
        if ($command_name === DriverCommand::IS_ELEMENT_DISPLAYED
            && (
                // When capabilities are missing in php-webdriver 1.13.x, always fallback to use the atom
                $this->getCapabilities() === null
                // If capabilities are present, use the atom only if condition matches
                || IsElementDisplayedAtom::match($this->getCapabilities()->getBrowserName())
            )
        ) {
            return (new IsElementDisplayedAtom($this))->execute($params);
        }

        $command = new WebDriverCommand(
            $this->sessionID,
            $command_name,
            $params
        );

        if ($this->executor) {
            $response = $this->executor->execute($command);

            return $response->getValue();
        }

        return null;
    }

    /**
     * Execute custom commands on remote end.
     * For example vendor-specific commands or other commands not implemented by php-webdriver.
     *
     * @see https://github.com/php-webdriver/php-webdriver/wiki/Custom-commands
     * @param string $endpointUrl
     * @param string $method
     * @param array $params
     * @return mixed|null
     */
    public function executeCustomCommand($endpointUrl, $method = 'GET', $params = [])
    {
        $command = new CustomWebDriverCommand(
            $this->sessionID,
            $endpointUrl,
            $method,
            $params
        );

        if ($this->executor) {
            $response = $this->executor->execute($command);

            return $response->getValue();
        }

        return null;
    }

    /**
     * @internal
     * @return bool
     */
    public function isW3cCompliant()
    {
        return $this->isW3cCompliant;
    }

    /**
     * Create instance based on response to NEW_SESSION command.
     * Also detect W3C/OSS dialect and setup the driver/executor accordingly.
     *
     * @internal
     * @return static
     */
    protected static function createFromResponse(WebDriverResponse $response, HttpCommandExecutor $commandExecutor)
    {
        $responseValue = $response->getValue();

        if (!$isW3cCompliant = isset($responseValue['capabilities'])) {
            $commandExecutor->disableW3cCompliance();
        }

        if ($isW3cCompliant) {
            $returnedCapabilities = DesiredCapabilities::createFromW3cCapabilities($responseValue['capabilities']);
        } else {
            $returnedCapabilities = new DesiredCapabilities($responseValue);
        }

        return new static($commandExecutor, $response->getSessionID(), $returnedCapabilities, $isW3cCompliant);
    }

    /**
     * Prepare arguments for JavaScript injection
     *
     * @param array $arguments
     * @return array
     */
    protected function prepareScriptArguments(array $arguments)
    {
        $args = [];
        foreach ($arguments as $key => $value) {
            if ($value instanceof WebDriverElement) {
                $args[$key] = [
                    $this->isW3cCompliant ?
                        JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER
                        : 'ELEMENT' => $value->getID(),
                ];
            } else {
                if (is_array($value)) {
                    $value = $this->prepareScriptArguments($value);
                }
                $args[$key] = $value;
            }
        }

        return $args;
    }

    /**
     * @return RemoteExecuteMethod
     */
    protected function getExecuteMethod()
    {
        if (!$this->executeMethod) {
            $this->executeMethod = new RemoteExecuteMethod($this);
        }

        return $this->executeMethod;
    }

    /**
     * Return the WebDriverElement with the given id.
     *
     * @param string $id The id of the element to be created.
     * @return RemoteWebElement
     */
    protected function newElement($id)
    {
        return new RemoteWebElement($this->getExecuteMethod(), $id, $this->isW3cCompliant);
    }

    /**
     * Cast legacy types (array or null) to DesiredCapabilities object. To be removed in future when instance of
     * DesiredCapabilities will be required.
     *
     * @param array|DesiredCapabilities|null $desired_capabilities
     * @return DesiredCapabilities
     */
    protected static function castToDesiredCapabilitiesObject($desired_capabilities = null)
    {
        if ($desired_capabilities === null) {
            return new DesiredCapabilities();
        }

        if (is_array($desired_capabilities)) {
            return new DesiredCapabilities($desired_capabilities);
        }

        return $desired_capabilities;
    }
}
