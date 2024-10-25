<?php
/**
 * Manages the rendering of pages in PMA
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function defined;
use function headers_sent;
use function http_response_code;
use function is_array;
use function is_scalar;
use function json_encode;
use function json_last_error_msg;
use function mb_strlen;
use function register_shutdown_function;
use function strlen;

use const PHP_SAPI;

/**
 * Singleton class used to manage the rendering of pages in PMA
 */
class ResponseRenderer
{
    /**
     * Response instance
     *
     * @static
     * @var ResponseRenderer
     */
    private static $instance;
    /**
     * Header instance
     *
     * @var Header
     */
    protected $header;
    /**
     * HTML data to be used in the response
     *
     * @var string
     */
    private $HTML;
    /**
     * An array of JSON key-value pairs
     * to be sent back for ajax requests
     *
     * @var array
     */
    private $JSON;
    /**
     * PhpMyAdmin\Footer instance
     *
     * @var Footer
     */
    protected $footer;
    /**
     * Whether we are servicing an ajax request.
     *
     * @var bool
     */
    protected $isAjax = false;
    /**
     * Whether response object is disabled
     *
     * @var bool
     */
    private $isDisabled;
    /**
     * Whether there were any errors during the processing of the request
     * Only used for ajax responses
     *
     * @var bool
     */
    protected $isSuccess;

    /**
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var array<int, string>
     */
    protected static $httpStatusMessages = [
        // Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        427 => 'Unassigned',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        430 => 'Unassigned',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        // Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Unassigned',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Creates a new class instance
     */
    private function __construct()
    {
        if (! defined('TESTSUITE')) {
            $buffer = OutputBuffering::getInstance();
            $buffer->start();
            register_shutdown_function([$this, 'response']);
        }

        $this->header = new Header();
        $this->HTML = '';
        $this->JSON = [];
        $this->footer = new Footer();

        $this->isSuccess = true;
        $this->isDisabled = false;
        $this->setAjax(! empty($_REQUEST['ajax_request']));
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
        $this->header->setAjax($this->isAjax);
        $this->footer->setAjax($this->isAjax);
    }

    /**
     * Returns the singleton Response object
     *
     * @return ResponseRenderer object
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new ResponseRenderer();
        }

        return self::$instance;
    }

    /**
     * Set the status of an ajax response,
     * whether it is a success or an error
     *
     * @param bool $state Whether the request was successfully processed
     */
    public function setRequestStatus(bool $state): void
    {
        $this->isSuccess = ($state === true);
    }

    /**
     * Returns true or false depending on whether
     * we are servicing an ajax request
     */
    public function isAjax(): bool
    {
        return $this->isAjax;
    }

    /**
     * Disables the rendering of the header
     * and the footer in responses
     */
    public function disable(): void
    {
        $this->header->disable();
        $this->footer->disable();
        $this->isDisabled = true;
    }

    /**
     * Returns a PhpMyAdmin\Header object
     *
     * @return Header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Returns a PhpMyAdmin\Footer object
     *
     * @return Footer
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * Append HTML code to the current output buffer
     */
    public function addHTML(string $content): void
    {
        $this->HTML .= $content;
    }

    /**
     * Add JSON code to the response
     *
     * @param string|int|array $json  Either a key (string) or an array or key-value pairs
     * @param mixed|null       $value Null, if passing an array in $json otherwise
     *                                it's a string value to the key
     */
    public function addJSON($json, $value = null): void
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $this->addJSON($key, $value);
            }
        } elseif ($value instanceof Message) {
            $this->JSON[$json] = $value->getDisplay();
        } else {
            $this->JSON[$json] = $value;
        }
    }

    /**
     * Renders the HTML response text
     */
    private function getDisplay(): string
    {
        // The header may contain nothing at all,
        // if its content was already rendered
        // and, in this case, the header will be
        // in the content part of the request
        $retval = $this->header->getDisplay();
        $retval .= $this->HTML;
        $retval .= $this->footer->getDisplay();

        return $retval;
    }

    /**
     * Sends a JSON response to the browser
     */
    private function ajaxResponse(): string
    {
        global $dbi;

        /* Avoid wrapping in case we're disabled */
        if ($this->isDisabled) {
            return $this->getDisplay();
        }

        if (! isset($this->JSON['message'])) {
            $this->JSON['message'] = $this->getDisplay();
        } elseif ($this->JSON['message'] instanceof Message) {
            $this->JSON['message'] = $this->JSON['message']->getDisplay();
        }

        if ($this->isSuccess) {
            $this->JSON['success'] = true;
        } else {
            $this->JSON['success'] = false;
            $this->JSON['error'] = $this->JSON['message'];
            unset($this->JSON['message']);
        }

        if ($this->isSuccess) {
            if (! isset($this->JSON['title'])) {
                $this->addJSON('title', '<title>' . $this->getHeader()->getPageTitle() . '</title>');
            }

            if (isset($dbi)) {
                $this->addJSON('menu', $this->getHeader()->getMenu()->getDisplay());
            }

            $this->addJSON('scripts', $this->getHeader()->getScripts()->getFiles());
            $this->addJSON('selflink', $this->getFooter()->getSelfUrl());
            $this->addJSON('displayMessage', $this->getHeader()->getMessage());

            $debug = $this->footer->getDebugMessage();
            if (empty($_REQUEST['no_debug']) && strlen($debug) > 0) {
                $this->addJSON('debug', $debug);
            }

            $errors = $this->footer->getErrorMessages();
            if (strlen($errors) > 0) {
                $this->addJSON('errors', $errors);
            }

            $promptPhpErrors = $GLOBALS['errorHandler']->hasErrorsForPrompt();
            $this->addJSON('promptPhpErrors', $promptPhpErrors);

            if (empty($GLOBALS['error_message'])) {
                // set current db, table and sql query in the querywindow
                // (this is for the bottom console)
                $query = '';
                $maxChars = $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'];
                if (isset($GLOBALS['sql_query']) && mb_strlen($GLOBALS['sql_query']) < $maxChars) {
                    $query = $GLOBALS['sql_query'];
                }

                $this->addJSON(
                    'reloadQuerywindow',
                    [
                        'db' => isset($GLOBALS['db']) && is_scalar($GLOBALS['db'])
                            ? (string) $GLOBALS['db'] : '',
                        'table' => isset($GLOBALS['table']) && is_scalar($GLOBALS['table'])
                            ? (string) $GLOBALS['table'] : '',
                        'sql_query' => $query,
                    ]
                );
                if (! empty($GLOBALS['focus_querywindow'])) {
                    $this->addJSON('_focusQuerywindow', $query);
                }

                if (! empty($GLOBALS['reload'])) {
                    $this->addJSON('reloadNavigation', 1);
                }

                $this->addJSON('params', $this->getHeader()->getJsParams());
            }
        }

        // Set the Content-Type header to JSON so that jQuery parses the
        // response correctly.
        Core::headerJSON();

        $result = json_encode($this->JSON);
        if ($result === false) {
            return (string) json_encode([
                'success' => false,
                'error' => 'JSON encoding failed: ' . json_last_error_msg(),
            ]);
        }

        return $result;
    }

    /**
     * Sends an HTML response to the browser
     */
    public function response(): void
    {
        $buffer = OutputBuffering::getInstance();
        if (empty($this->HTML)) {
            $this->HTML = $buffer->getContents();
        }

        if ($this->isAjax()) {
            echo $this->ajaxResponse();
        } else {
            echo $this->getDisplay();
        }

        $buffer->flush();
        exit;
    }

    /**
     * Wrapper around PHP's header() function.
     *
     * @param string $text header string
     */
    public function header($text): void
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
        \header($text);
    }

    /**
     * Wrapper around PHP's headers_sent() function.
     */
    public function headersSent(): bool
    {
        return headers_sent();
    }

    /**
     * Wrapper around PHP's http_response_code() function.
     *
     * @param int $response_code will set the response code.
     */
    public function httpResponseCode($response_code): void
    {
        http_response_code($response_code);
    }

    /**
     * Sets http response code.
     *
     * @param int $responseCode will set the response code.
     */
    public function setHttpResponseCode(int $responseCode): void
    {
        $this->httpResponseCode($responseCode);
        $header = 'status: ' . $responseCode . ' ';
        if (isset(static::$httpStatusMessages[$responseCode])) {
            $header .= static::$httpStatusMessages[$responseCode];
        } else {
            $header .= 'Web server is down';
        }

        if (PHP_SAPI === 'cgi-fcgi') {
            return;
        }

        $this->header($header);
    }

    /**
     * Generate header for 303
     *
     * @param string $location will set location to redirect.
     */
    public function generateHeader303($location): void
    {
        $this->setHttpResponseCode(303);
        $this->header('Location: ' . $location);
        if (! defined('TESTSUITE')) {
            exit;
        }
    }

    /**
     * Configures response for the login page
     *
     * @return bool Whether caller should exit
     */
    public function loginPage(): bool
    {
        /* Handle AJAX redirection */
        if ($this->isAjax()) {
            $this->setRequestStatus(false);
            // redirect_flag redirects to the login page
            $this->addJSON('redirect_flag', '1');

            return true;
        }

        $this->getFooter()->setMinimal();
        $header = $this->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle('phpMyAdmin');
        $header->disableMenuAndConsole();
        $header->disableWarnings();

        return false;
    }
}
