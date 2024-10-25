<?php

namespace Facebook\WebDriver\Remote;

use BadMethodCallException;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverCommandExecutor;
use InvalidArgumentException;

/**
 * Command executor talking to the standalone server via HTTP.
 */
class HttpCommandExecutor implements WebDriverCommandExecutor
{
    const DEFAULT_HTTP_HEADERS = [
        'Content-Type: application/json;charset=UTF-8',
        'Accept: application/json',
    ];

    /**
     * @see https://github.com/SeleniumHQ/selenium/wiki/JsonWireProtocol#command-reference
     */
    protected static $commands = [
        DriverCommand::ACCEPT_ALERT => ['method' => 'POST', 'url' => '/session/:sessionId/accept_alert'],
        DriverCommand::ADD_COOKIE => ['method' => 'POST', 'url' => '/session/:sessionId/cookie'],
        DriverCommand::CLEAR_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element/:id/clear'],
        DriverCommand::CLICK_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element/:id/click'],
        DriverCommand::CLOSE => ['method' => 'DELETE', 'url' => '/session/:sessionId/window'],
        DriverCommand::DELETE_ALL_COOKIES => ['method' => 'DELETE', 'url' => '/session/:sessionId/cookie'],
        DriverCommand::DELETE_COOKIE => ['method' => 'DELETE', 'url' => '/session/:sessionId/cookie/:name'],
        DriverCommand::DISMISS_ALERT => ['method' => 'POST', 'url' => '/session/:sessionId/dismiss_alert'],
        DriverCommand::ELEMENT_EQUALS => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/equals/:other'],
        DriverCommand::FIND_CHILD_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element/:id/element'],
        DriverCommand::FIND_CHILD_ELEMENTS => ['method' => 'POST', 'url' => '/session/:sessionId/element/:id/elements'],
        DriverCommand::EXECUTE_SCRIPT => ['method' => 'POST', 'url' => '/session/:sessionId/execute'],
        DriverCommand::EXECUTE_ASYNC_SCRIPT => ['method' => 'POST', 'url' => '/session/:sessionId/execute_async'],
        DriverCommand::FIND_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element'],
        DriverCommand::FIND_ELEMENTS => ['method' => 'POST', 'url' => '/session/:sessionId/elements'],
        DriverCommand::SWITCH_TO_FRAME => ['method' => 'POST', 'url' => '/session/:sessionId/frame'],
        DriverCommand::SWITCH_TO_PARENT_FRAME => ['method' => 'POST', 'url' => '/session/:sessionId/frame/parent'],
        DriverCommand::SWITCH_TO_WINDOW => ['method' => 'POST', 'url' => '/session/:sessionId/window'],
        DriverCommand::GET => ['method' => 'POST', 'url' => '/session/:sessionId/url'],
        DriverCommand::GET_ACTIVE_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element/active'],
        DriverCommand::GET_ALERT_TEXT => ['method' => 'GET', 'url' => '/session/:sessionId/alert_text'],
        DriverCommand::GET_ALL_COOKIES => ['method' => 'GET', 'url' => '/session/:sessionId/cookie'],
        DriverCommand::GET_NAMED_COOKIE => ['method' => 'GET', 'url' => '/session/:sessionId/cookie/:name'],
        DriverCommand::GET_ALL_SESSIONS => ['method' => 'GET', 'url' => '/sessions'],
        DriverCommand::GET_AVAILABLE_LOG_TYPES => ['method' => 'GET', 'url' => '/session/:sessionId/log/types'],
        DriverCommand::GET_CURRENT_URL => ['method' => 'GET', 'url' => '/session/:sessionId/url'],
        DriverCommand::GET_CURRENT_WINDOW_HANDLE => ['method' => 'GET', 'url' => '/session/:sessionId/window_handle'],
        DriverCommand::GET_ELEMENT_ATTRIBUTE => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/attribute/:name',
        ],
        DriverCommand::GET_ELEMENT_VALUE_OF_CSS_PROPERTY => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/css/:propertyName',
        ],
        DriverCommand::GET_ELEMENT_LOCATION => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/location',
        ],
        DriverCommand::GET_ELEMENT_LOCATION_ONCE_SCROLLED_INTO_VIEW => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/location_in_view',
        ],
        DriverCommand::GET_ELEMENT_SIZE => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/size'],
        DriverCommand::GET_ELEMENT_TAG_NAME => ['method' => 'GET',  'url' => '/session/:sessionId/element/:id/name'],
        DriverCommand::GET_ELEMENT_TEXT => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/text'],
        DriverCommand::GET_LOG => ['method' => 'POST', 'url' => '/session/:sessionId/log'],
        DriverCommand::GET_PAGE_SOURCE => ['method' => 'GET', 'url' => '/session/:sessionId/source'],
        DriverCommand::GET_SCREEN_ORIENTATION => ['method' => 'GET', 'url' => '/session/:sessionId/orientation'],
        DriverCommand::GET_CAPABILITIES => ['method' => 'GET', 'url' => '/session/:sessionId'],
        DriverCommand::GET_TITLE => ['method' => 'GET', 'url' => '/session/:sessionId/title'],
        DriverCommand::GET_WINDOW_HANDLES => ['method' => 'GET', 'url' => '/session/:sessionId/window_handles'],
        DriverCommand::GET_WINDOW_POSITION => [
            'method' => 'GET',
            'url' => '/session/:sessionId/window/:windowHandle/position',
        ],
        DriverCommand::GET_WINDOW_SIZE => ['method' => 'GET', 'url' => '/session/:sessionId/window/:windowHandle/size'],
        DriverCommand::GO_BACK => ['method' => 'POST', 'url' => '/session/:sessionId/back'],
        DriverCommand::GO_FORWARD => ['method' => 'POST', 'url' => '/session/:sessionId/forward'],
        DriverCommand::IS_ELEMENT_DISPLAYED => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/displayed',
        ],
        DriverCommand::IS_ELEMENT_ENABLED => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/enabled'],
        DriverCommand::IS_ELEMENT_SELECTED => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/selected'],
        DriverCommand::MAXIMIZE_WINDOW => [
            'method' => 'POST',
            'url' => '/session/:sessionId/window/:windowHandle/maximize',
        ],
        DriverCommand::MOUSE_DOWN => ['method' => 'POST', 'url' => '/session/:sessionId/buttondown'],
        DriverCommand::MOUSE_UP => ['method' => 'POST', 'url' => '/session/:sessionId/buttonup'],
        DriverCommand::CLICK => ['method' => 'POST', 'url' => '/session/:sessionId/click'],
        DriverCommand::DOUBLE_CLICK => ['method' => 'POST', 'url' => '/session/:sessionId/doubleclick'],
        DriverCommand::MOVE_TO => ['method' => 'POST', 'url' => '/session/:sessionId/moveto'],
        DriverCommand::NEW_SESSION => ['method' => 'POST', 'url' => '/session'],
        DriverCommand::QUIT => ['method' => 'DELETE', 'url' => '/session/:sessionId'],
        DriverCommand::REFRESH => ['method' => 'POST', 'url' => '/session/:sessionId/refresh'],
        DriverCommand::UPLOAD_FILE => ['method' => 'POST', 'url' => '/session/:sessionId/file'], // undocumented
        DriverCommand::SEND_KEYS_TO_ACTIVE_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/keys'],
        DriverCommand::SET_ALERT_VALUE => ['method' => 'POST', 'url' => '/session/:sessionId/alert_text'],
        DriverCommand::SEND_KEYS_TO_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element/:id/value'],
        DriverCommand::IMPLICITLY_WAIT => ['method' => 'POST', 'url' => '/session/:sessionId/timeouts/implicit_wait'],
        DriverCommand::SET_SCREEN_ORIENTATION => ['method' => 'POST', 'url' => '/session/:sessionId/orientation'],
        DriverCommand::SET_TIMEOUT => ['method' => 'POST', 'url' => '/session/:sessionId/timeouts'],
        DriverCommand::SET_SCRIPT_TIMEOUT => ['method' => 'POST', 'url' => '/session/:sessionId/timeouts/async_script'],
        DriverCommand::SET_WINDOW_POSITION => [
            'method' => 'POST',
            'url' => '/session/:sessionId/window/:windowHandle/position',
        ],
        DriverCommand::SET_WINDOW_SIZE => [
            'method' => 'POST',
            'url' => '/session/:sessionId/window/:windowHandle/size',
        ],
        DriverCommand::STATUS => ['method' => 'GET', 'url' => '/status'],
        DriverCommand::SUBMIT_ELEMENT => ['method' => 'POST', 'url' => '/session/:sessionId/element/:id/submit'],
        DriverCommand::SCREENSHOT => ['method' => 'GET', 'url' => '/session/:sessionId/screenshot'],
        DriverCommand::TAKE_ELEMENT_SCREENSHOT => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/screenshot',
        ],
        DriverCommand::TOUCH_SINGLE_TAP => ['method' => 'POST', 'url' => '/session/:sessionId/touch/click'],
        DriverCommand::TOUCH_DOWN => ['method' => 'POST', 'url' => '/session/:sessionId/touch/down'],
        DriverCommand::TOUCH_DOUBLE_TAP => ['method' => 'POST', 'url' => '/session/:sessionId/touch/doubleclick'],
        DriverCommand::TOUCH_FLICK => ['method' => 'POST', 'url' => '/session/:sessionId/touch/flick'],
        DriverCommand::TOUCH_LONG_PRESS => ['method' => 'POST', 'url' => '/session/:sessionId/touch/longclick'],
        DriverCommand::TOUCH_MOVE => ['method' => 'POST', 'url' => '/session/:sessionId/touch/move'],
        DriverCommand::TOUCH_SCROLL => ['method' => 'POST', 'url' => '/session/:sessionId/touch/scroll'],
        DriverCommand::TOUCH_UP => ['method' => 'POST', 'url' => '/session/:sessionId/touch/up'],
        DriverCommand::CUSTOM_COMMAND => [],
    ];
    /**
     * @var array Will be merged with $commands
     */
    protected static $w3cCompliantCommands = [
        DriverCommand::ACCEPT_ALERT => ['method' => 'POST', 'url' => '/session/:sessionId/alert/accept'],
        DriverCommand::ACTIONS => ['method' => 'POST', 'url' => '/session/:sessionId/actions'],
        DriverCommand::DISMISS_ALERT => ['method' => 'POST', 'url' => '/session/:sessionId/alert/dismiss'],
        DriverCommand::EXECUTE_ASYNC_SCRIPT => ['method' => 'POST', 'url' => '/session/:sessionId/execute/async'],
        DriverCommand::EXECUTE_SCRIPT => ['method' => 'POST', 'url' => '/session/:sessionId/execute/sync'],
        DriverCommand::FIND_ELEMENT_FROM_SHADOW_ROOT => [
            'method' => 'POST',
            'url' => '/session/:sessionId/shadow/:id/element',
        ],
        DriverCommand::FIND_ELEMENTS_FROM_SHADOW_ROOT => [
            'method' => 'POST',
            'url' => '/session/:sessionId/shadow/:id/elements',
        ],
        DriverCommand::FULLSCREEN_WINDOW => ['method' => 'POST', 'url' => '/session/:sessionId/window/fullscreen'],
        DriverCommand::GET_ACTIVE_ELEMENT => ['method' => 'GET', 'url' => '/session/:sessionId/element/active'],
        DriverCommand::GET_ALERT_TEXT => ['method' => 'GET', 'url' => '/session/:sessionId/alert/text'],
        DriverCommand::GET_CURRENT_WINDOW_HANDLE => ['method' => 'GET', 'url' => '/session/:sessionId/window'],
        DriverCommand::GET_ELEMENT_LOCATION => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/rect'],
        DriverCommand::GET_ELEMENT_PROPERTY => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/property/:name',
        ],
        DriverCommand::GET_ELEMENT_SHADOW_ROOT => [
            'method' => 'GET',
            'url' => '/session/:sessionId/element/:id/shadow',
        ],
        DriverCommand::GET_ELEMENT_SIZE => ['method' => 'GET', 'url' => '/session/:sessionId/element/:id/rect'],
        DriverCommand::GET_WINDOW_HANDLES => ['method' => 'GET', 'url' => '/session/:sessionId/window/handles'],
        DriverCommand::GET_WINDOW_POSITION => ['method' => 'GET', 'url' => '/session/:sessionId/window/rect'],
        DriverCommand::GET_WINDOW_SIZE => ['method' => 'GET', 'url' => '/session/:sessionId/window/rect'],
        DriverCommand::IMPLICITLY_WAIT => ['method' => 'POST', 'url' => '/session/:sessionId/timeouts'],
        DriverCommand::MAXIMIZE_WINDOW => ['method' => 'POST', 'url' => '/session/:sessionId/window/maximize'],
        DriverCommand::MINIMIZE_WINDOW => ['method' => 'POST', 'url' => '/session/:sessionId/window/minimize'],
        DriverCommand::NEW_WINDOW => ['method' => 'POST', 'url' => '/session/:sessionId/window/new'],
        DriverCommand::SET_ALERT_VALUE => ['method' => 'POST', 'url' => '/session/:sessionId/alert/text'],
        DriverCommand::SET_SCRIPT_TIMEOUT => ['method' => 'POST', 'url' => '/session/:sessionId/timeouts'],
        DriverCommand::SET_TIMEOUT => ['method' => 'POST', 'url' => '/session/:sessionId/timeouts'],
        DriverCommand::SET_WINDOW_SIZE => ['method' => 'POST', 'url' => '/session/:sessionId/window/rect'],
        DriverCommand::SET_WINDOW_POSITION => ['method' => 'POST', 'url' => '/session/:sessionId/window/rect'],
    ];
    /**
     * @var string
     */
    protected $url;
    /**
     * @var resource
     */
    protected $curl;
    /**
     * @var bool
     */
    protected $isW3cCompliant = true;

    /**
     * @param string $url
     * @param string|null $http_proxy
     * @param int|null $http_proxy_port
     */
    public function __construct($url, $http_proxy = null, $http_proxy_port = null)
    {
        self::$w3cCompliantCommands = array_merge(self::$commands, self::$w3cCompliantCommands);

        $this->url = $url;
        $this->curl = curl_init();

        if (!empty($http_proxy)) {
            curl_setopt($this->curl, CURLOPT_PROXY, $http_proxy);
            if ($http_proxy_port !== null) {
                curl_setopt($this->curl, CURLOPT_PROXYPORT, $http_proxy_port);
            }
        }

        // Get credentials from $url (if any)
        $matches = null;
        if (preg_match("/^(https?:\/\/)(.*):(.*)@(.*?)/U", $url, $matches)) {
            $this->url = $matches[1] . $matches[4];
            $auth_creds = $matches[2] . ':' . $matches[3];
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($this->curl, CURLOPT_USERPWD, $auth_creds);
        }

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, static::DEFAULT_HTTP_HEADERS);

        $this->setConnectionTimeout(30 * 1000); // 30 seconds
        $this->setRequestTimeout(180 * 1000); // 3 minutes
    }

    public function disableW3cCompliance()
    {
        $this->isW3cCompliant = false;
    }

    /**
     * Set timeout for the connect phase
     *
     * @param int $timeout_in_ms Timeout in milliseconds
     * @return HttpCommandExecutor
     */
    public function setConnectionTimeout($timeout_in_ms)
    {
        // There is a PHP bug in some versions which didn't define the constant.
        curl_setopt(
            $this->curl,
            /* CURLOPT_CONNECTTIMEOUT_MS */
            156,
            $timeout_in_ms
        );

        return $this;
    }

    /**
     * Set the maximum time of a request
     *
     * @param int $timeout_in_ms Timeout in milliseconds
     * @return HttpCommandExecutor
     */
    public function setRequestTimeout($timeout_in_ms)
    {
        // There is a PHP bug in some versions (at least for PHP 5.3.3) which
        // didn't define the constant.
        curl_setopt(
            $this->curl,
            /* CURLOPT_TIMEOUT_MS */
            155,
            $timeout_in_ms
        );

        return $this;
    }

    /**
     * @param WebDriverCommand $command
     *
     * @throws WebDriverException
     * @return WebDriverResponse
     */
    public function execute(WebDriverCommand $command)
    {
        $http_options = $this->getCommandHttpOptions($command);
        $http_method = $http_options['method'];
        $url = $http_options['url'];

        $sessionID = $command->getSessionID();
        $url = str_replace(':sessionId', $sessionID === null ? '' : $sessionID, $url);
        $params = $command->getParameters();
        foreach ($params as $name => $value) {
            if ($name[0] === ':') {
                $url = str_replace($name, $value, $url);
                unset($params[$name]);
            }
        }

        if (is_array($params) && !empty($params) && $http_method !== 'POST') {
            throw new BadMethodCallException(sprintf(
                'The http method called for %s is %s but it has to be POST' .
                ' if you want to pass the JSON params %s',
                $url,
                $http_method,
                json_encode($params)
            ));
        }

        curl_setopt($this->curl, CURLOPT_URL, $this->url . $url);

        // https://github.com/facebook/php-webdriver/issues/173
        if ($command->getName() === DriverCommand::NEW_SESSION) {
            curl_setopt($this->curl, CURLOPT_POST, 1);
        } else {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $http_method);
        }

        if (in_array($http_method, ['POST', 'PUT'], true)) {
            // Disable sending 'Expect: 100-Continue' header, as it is causing issues with eg. squid proxy
            // https://tools.ietf.org/html/rfc7231#section-5.1.1
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array_merge(static::DEFAULT_HTTP_HEADERS, ['Expect:']));
        } else {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, static::DEFAULT_HTTP_HEADERS);
        }

        $encoded_params = null;

        if ($http_method === 'POST') {
            if (is_array($params) && !empty($params)) {
                $encoded_params = json_encode($params);
            } elseif ($this->isW3cCompliant) {
                // POST body must be valid JSON in W3C, even if empty: https://www.w3.org/TR/webdriver/#processing-model
                $encoded_params = '{}';
            }
        }

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_params);

        $raw_results = trim(curl_exec($this->curl));

        if ($error = curl_error($this->curl)) {
            $msg = sprintf(
                'Curl error thrown for http %s to %s',
                $http_method,
                $url
            );
            if (is_array($params) && !empty($params)) {
                $msg .= sprintf(' with params: %s', json_encode($params, JSON_UNESCAPED_SLASHES));
            }

            throw new WebDriverCurlException($msg . "\n\n" . $error);
        }

        $results = json_decode($raw_results, true);

        if ($results === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new WebDriverException(
                sprintf(
                    "JSON decoding of remote response failed.\n" .
                    "Error code: %d\n" .
                    "The response: '%s'\n",
                    json_last_error(),
                    $raw_results
                )
            );
        }

        $value = null;
        if (is_array($results) && array_key_exists('value', $results)) {
            $value = $results['value'];
        }

        $message = null;
        if (is_array($value) && array_key_exists('message', $value)) {
            $message = $value['message'];
        }

        $sessionId = null;
        if (is_array($value) && array_key_exists('sessionId', $value)) {
            // W3C's WebDriver
            $sessionId = $value['sessionId'];
        } elseif (is_array($results) && array_key_exists('sessionId', $results)) {
            // Legacy JsonWire
            $sessionId = $results['sessionId'];
        }

        // @see https://w3c.github.io/webdriver/webdriver-spec.html#handling-errors
        if (isset($value['error'])) {
            // W3C's WebDriver
            WebDriverException::throwException($value['error'], $message, $results);
        }

        $status = isset($results['status']) ? $results['status'] : 0;
        if ($status !== 0) {
            // Legacy JsonWire
            WebDriverException::throwException($status, $message, $results);
        }

        $response = new WebDriverResponse($sessionId);

        return $response
            ->setStatus($status)
            ->setValue($value);
    }

    /**
     * @return string
     */
    public function getAddressOfRemoteServer()
    {
        return $this->url;
    }

    /**
     * @return array
     */
    protected function getCommandHttpOptions(WebDriverCommand $command)
    {
        $commandName = $command->getName();
        if (!isset(self::$commands[$commandName])) {
            if ($this->isW3cCompliant && !isset(self::$w3cCompliantCommands[$commandName])) {
                throw new InvalidArgumentException($command->getName() . ' is not a valid command.');
            }
        }

        if ($this->isW3cCompliant) {
            $raw = self::$w3cCompliantCommands[$command->getName()];
        } else {
            $raw = self::$commands[$command->getName()];
        }

        if ($command instanceof CustomWebDriverCommand) {
            $url = $command->getCustomUrl();
            $method = $command->getCustomMethod();
        } else {
            $url = $raw['url'];
            $method = $raw['method'];
        }

        return [
            'url' => $url,
            'method' => $method,
        ];
    }
}
