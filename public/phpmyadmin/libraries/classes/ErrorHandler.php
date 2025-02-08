<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ErrorException;
use Throwable;

use function __;
use function array_splice;
use function count;
use function defined;
use function error_reporting;
use function get_class;
use function htmlspecialchars;
use function set_error_handler;
use function set_exception_handler;
use function trigger_error;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use const PHP_VERSION_ID;

/**
 * handling errors
 */
class ErrorHandler
{
    /**
     * holds errors to be displayed or reported later ...
     *
     * @var Error[]
     */
    protected $errors = [];

    /**
     * Hide location of errors
     *
     * @var bool
     */
    protected $hideLocation = false;

    /**
     * Initial error reporting state
     *
     * @var int
     */
    protected $errorReporting = 0;

    public function __construct()
    {
        /**
         * Do not set ourselves as error handler in case of testsuite.
         *
         * This behavior is not tested there and breaks other tests as they
         * rely on PHPUnit doing it's own error handling which we break here.
         */
        if (! defined('TESTSUITE')) {
            set_exception_handler([$this, 'handleException']);
            set_error_handler([$this, 'handleError']);
        }

        if (! Util::isErrorReportingAvailable()) {
            return;
        }

        $this->errorReporting = error_reporting();
    }

    /**
     * Destructor
     *
     * stores errors in session
     */
    public function __destruct()
    {
        if (! isset($_SESSION['errors'])) {
            $_SESSION['errors'] = [];
        }

        // remember only not displayed errors
        foreach ($this->errors as $key => $error) {
            /**
             * We don't want to store all errors here as it would
             * explode user session.
             */
            if (count($_SESSION['errors']) >= 10) {
                $error = new Error(
                    0,
                    __('Too many error messages, some are not displayed.'),
                    __FILE__,
                    __LINE__
                );
                $_SESSION['errors'][$error->getHash()] = $error;
                break;
            }

            if ((! ($error instanceof Error)) || $error->isDisplayed()) {
                continue;
            }

            $_SESSION['errors'][$key] = $error;
        }
    }

    /**
     * Toggles location hiding
     *
     * @param bool $hide Whether to hide
     */
    public function setHideLocation(bool $hide): void
    {
        $this->hideLocation = $hide;
    }

    /**
     * returns array with all errors
     *
     * @param bool $check Whether to check for session errors
     *
     * @return Error[]
     */
    public function getErrors(bool $check = true): array
    {
        if ($check) {
            $this->checkSavedErrors();
        }

        return $this->errors;
    }

    /**
     * returns the errors occurred in the current run only.
     * Does not include the errors saved in the SESSION
     *
     * @return Error[]
     */
    public function getCurrentErrors(): array
    {
        return $this->errors;
    }

    /**
     * Pops recent errors from the storage
     *
     * @param int $count Old error count (amount of errors to splice)
     *
     * @return Error[] The non spliced elements (total-$count)
     */
    public function sliceErrors(int $count): array
    {
        // store the errors before any operation, example number of items: 10
        $errors = $this->getErrors(false);

        // before array_splice $this->errors has 10 elements
        // cut out $count items out, let's say $count = 9
        // $errors will now contain 10 - 9 = 1 elements
        // $this->errors will contain the 9 elements left
        $this->errors = array_splice($errors, 0, $count);

        return $errors;
    }

    /**
     * Error handler - called when errors are triggered/occurred
     *
     * This calls the addError() function, escaping the error string
     * Ignores the errors wherever Error Control Operator (@) is used.
     *
     * @param int    $errno   error number
     * @param string $errstr  error string
     * @param string $errfile error file
     * @param int    $errline error line
     *
     * @throws ErrorException
     */
    public function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): void {
        global $cfg;

        if (Util::isErrorReportingAvailable()) {
            /**
            * Check if Error Control Operator (@) was used, but still show
            * user errors even in this case.
            * See: https://github.com/phpmyadmin/phpmyadmin/issues/16729
            */
            $isSilenced = ! (error_reporting() & $errno);
            if (PHP_VERSION_ID < 80000) {
                $isSilenced = error_reporting() == 0;
            }

            if (isset($cfg['environment']) && $cfg['environment'] === 'development' && ! $isSilenced) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }

            if (
                $isSilenced &&
                $this->errorReporting != 0 &&
                ($errno & (E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_DEPRECATED)) == 0
            ) {
                return;
            }
        } else {
            if (($errno & (E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_DEPRECATED)) == 0) {
                return;
            }
        }

        $this->addError($errstr, $errno, $errfile, $errline, true);
    }

    /**
     * Hides exception if it's not in the development environment.
     */
    public function handleException(Throwable $exception): void
    {
        $config = $GLOBALS['config'] ?? null;
        $this->hideLocation = ! $config instanceof Config || $config->get('environment') !== 'development';
        $message = get_class($exception);
        if (! ($exception instanceof \Error) || ! $this->hideLocation) {
            $message .= ': ' . $exception->getMessage();
        }

        $this->addError(
            $message,
            (int) $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        );
    }

    /**
     * Add an error; can also be called directly (with or without escaping)
     *
     * The following error types cannot be handled with a user defined function:
     * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
     * E_COMPILE_WARNING,
     * and most of E_STRICT raised in the file where set_error_handler() is called.
     *
     * Do not use the context parameter as we want to avoid storing the
     * complete $GLOBALS inside $_SESSION['errors']
     *
     * @param string $errstr  error string
     * @param int    $errno   error number
     * @param string $errfile error file
     * @param int    $errline error line
     * @param bool   $escape  whether to escape the error string
     */
    public function addError(
        string $errstr,
        int $errno,
        string $errfile,
        int $errline,
        bool $escape = true
    ): void {
        if ($escape) {
            $errstr = htmlspecialchars($errstr);
        }

        // create error object
        $error = new Error($errno, $errstr, $errfile, $errline);
        $error->setHideLocation($this->hideLocation);

        // Deprecation errors will be shown in development environment, as they will have a different number.
        if ($error->getNumber() !== E_DEPRECATED) {
            // do not repeat errors
            $this->errors[$error->getHash()] = $error;
        }

        switch ($error->getNumber()) {
            case 2048: // E_STRICT
            case E_DEPRECATED:
            case E_NOTICE:
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                /* Avoid rendering BB code in PHP errors */
                $error->setBBCode(false);
                break;
            case E_USER_NOTICE:
            case E_USER_WARNING:
            case E_USER_ERROR:
            case E_USER_DEPRECATED:
                // just collect the error
                // display is called from outside
                break;
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            default:
                // FATAL error, display it and exit
                $this->dispFatalError($error);
                if (! defined('TESTSUITE')) {
                    exit; // @codeCoverageIgnore
                }
        }
    }

    /**
     * trigger a custom error
     *
     * @param string $errorInfo   error message
     * @param int    $errorNumber error number
     * @psalm-param 256|512|1024|16384 $errorNumber
     */
    public function triggerError(string $errorInfo, int $errorNumber = E_USER_NOTICE): void
    {
        // we could also extract file and line from backtrace
        // and call handleError() directly
        trigger_error($errorInfo, $errorNumber);
    }

    /**
     * display fatal error and exit
     *
     * @param Error $error the error
     */
    protected function dispFatalError(Error $error): void
    {
        $response = ResponseRenderer::getInstance();
        if (! $response->headersSent()) {
            $response->disable();
            $response->addHTML('<html><head><title>');
            $response->addHTML($error->getTitle());
            $response->addHTML('</title></head>' . "\n");
        }

        $response->addHTML($error->getDisplay());
        $response->addHTML('</body></html>');
        if (! defined('TESTSUITE')) {
            exit;
        }
    }

    /**
     * Displays user errors not displayed
     */
    public function dispUserErrors(): void
    {
        echo $this->getDispUserErrors();
    }

    /**
     * Renders user errors not displayed
     */
    public function getDispUserErrors(): string
    {
        $retval = '';
        foreach ($this->getErrors() as $error) {
            if (! $error->isUserError() || $error->isDisplayed()) {
                continue;
            }

            $retval .= $error->getDisplay();
        }

        return $retval;
    }

    /**
     * renders errors not displayed
     */
    public function getDispErrors(): string
    {
        $retval = '';
        // display errors if SendErrorReports is set to 'ask'.
        if ($GLOBALS['cfg']['SendErrorReports'] !== 'never') {
            foreach ($this->getErrors() as $error) {
                if ($error->isDisplayed()) {
                    continue;
                }

                $retval .= $error->getDisplay();
            }
        } else {
            $retval .= $this->getDispUserErrors();
        }

        // if preference is not 'never' and
        // there are 'actual' errors to be reported
        if ($GLOBALS['cfg']['SendErrorReports'] !== 'never' && $this->countErrors() != $this->countUserErrors()) {
            // add report button.
            $retval .= '<form method="post" action="' . Url::getFromRoute('/error-report')
                    . '" id="pma_report_errors_form"';
            if ($GLOBALS['cfg']['SendErrorReports'] === 'always') {
                // in case of 'always', generate 'invisible' form.
                $retval .= ' class="hide"';
            }

            $retval .= '>';
            $retval .= Url::getHiddenFields([
                'exception_type' => 'php',
                'send_error_report' => '1',
                'server' => $GLOBALS['server'],
            ]);
            $retval .= '<input type="submit" value="'
                    . __('Report')
                    . '" id="pma_report_errors" class="btn btn-primary float-end">'
                    . '<input type="checkbox" name="always_send"'
                    . ' id="errorReportAlwaysSendCheckbox" value="true">'
                    . '<label for="errorReportAlwaysSendCheckbox">'
                    . __('Automatically send report next time')
                    . '</label>';

            if ($GLOBALS['cfg']['SendErrorReports'] === 'ask') {
                // add ignore buttons
                $retval .= '<input type="submit" value="'
                        . __('Ignore')
                        . '" id="pma_ignore_errors_bottom" class="btn btn-secondary float-end">';
            }

            $retval .= '<input type="submit" value="'
                    . __('Ignore All')
                    . '" id="pma_ignore_all_errors_bottom" class="btn btn-secondary float-end">';
            $retval .= '</form>';
        }

        return $retval;
    }

    /**
     * look in session for saved errors
     */
    protected function checkSavedErrors(): void
    {
        if (! isset($_SESSION['errors'])) {
            return;
        }

        // restore saved errors
        foreach ($_SESSION['errors'] as $hash => $error) {
            if (! ($error instanceof Error) || isset($this->errors[$hash])) {
                continue;
            }

            $this->errors[$hash] = $error;
        }

        // delete stored errors
        $_SESSION['errors'] = [];
        unset($_SESSION['errors']);
    }

    /**
     * return count of errors
     *
     * @param bool $check Whether to check for session errors
     *
     * @return int number of errors occurred
     */
    public function countErrors(bool $check = true): int
    {
        return count($this->getErrors($check));
    }

    /**
     * return count of user errors
     *
     * @return int number of user errors occurred
     */
    public function countUserErrors(): int
    {
        $count = 0;
        if ($this->countErrors()) {
            foreach ($this->getErrors() as $error) {
                if (! $error->isUserError()) {
                    continue;
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * whether use errors occurred or not
     */
    public function hasUserErrors(): bool
    {
        return (bool) $this->countUserErrors();
    }

    /**
     * whether errors occurred or not
     */
    public function hasErrors(): bool
    {
        return (bool) $this->countErrors();
    }

    /**
     * number of errors to be displayed
     *
     * @return int number of errors to be displayed
     */
    public function countDisplayErrors(): int
    {
        if ($GLOBALS['cfg']['SendErrorReports'] !== 'never') {
            return $this->countErrors();
        }

        return $this->countUserErrors();
    }

    /**
     * whether there are errors to display or not
     */
    public function hasDisplayErrors(): bool
    {
        return (bool) $this->countDisplayErrors();
    }

    /**
     * Deletes previously stored errors in SESSION.
     * Saves current errors in session as previous errors.
     * Required to save current errors in case  'ask'
     */
    public function savePreviousErrors(): void
    {
        unset($_SESSION['prev_errors']);
        $_SESSION['prev_errors'] = $GLOBALS['errorHandler']->getCurrentErrors();
    }

    /**
     * Function to check if there are any errors to be prompted.
     * Needed because user warnings raised are
     *      also collected by global error handler.
     * This distinguishes between the actual errors
     *      and user errors raised to warn user.
     */
    public function hasErrorsForPrompt(): bool
    {
        return $GLOBALS['cfg']['SendErrorReports'] !== 'never'
            && $this->countErrors() != $this->countUserErrors();
    }

    /**
     * Function to report all the collected php errors.
     * Must be called at the end of each script
     *      by the $GLOBALS['errorHandler'] only.
     */
    public function reportErrors(): void
    {
        // if there're no actual errors,
        if (! $this->hasErrors() || $this->countErrors() == $this->countUserErrors()) {
            // then simply return.
            return;
        }

        // Delete all the prev_errors in session & store new prev_errors in session
        $this->savePreviousErrors();
        $response = ResponseRenderer::getInstance();
        $jsCode = '';
        if ($GLOBALS['cfg']['SendErrorReports'] === 'always') {
            if ($response->isAjax()) {
                // set flag for automatic report submission.
                $response->addJSON('sendErrorAlways', '1');
            } else {
                // send the error reports asynchronously & without asking user
                $jsCode .= '$("#pma_report_errors_form").submit();'
                        . 'Functions.ajaxShowMessage(
                            Messages.phpErrorsBeingSubmitted, false
                        );';
                // js code to appropriate focusing,
                $jsCode .= '$("html, body").animate({
                                scrollTop:$(document).height()
                            }, "slow");';
            }
        } elseif ($GLOBALS['cfg']['SendErrorReports'] === 'ask') {
            //ask user whether to submit errors or not.
            if (! $response->isAjax()) {
                // js code to show appropriate msgs, event binding & focusing.
                $jsCode = 'Functions.ajaxShowMessage(Messages.phpErrorsFound);'
                        . '$("#pma_ignore_errors_popup").on("click", function() {
                            Functions.ignorePhpErrors()
                        });'
                        . '$("#pma_ignore_all_errors_popup").on("click",
                            function() {
                                Functions.ignorePhpErrors(false)
                            });'
                        . '$("#pma_ignore_errors_bottom").on("click", function(e) {
                            e.preventDefault();
                            Functions.ignorePhpErrors()
                        });'
                        . '$("#pma_ignore_all_errors_bottom").on("click",
                            function(e) {
                                e.preventDefault();
                                Functions.ignorePhpErrors(false)
                            });'
                        . '$("html, body").animate({
                            scrollTop:$(document).height()
                        }, "slow");';
            }
        }

        // The errors are already sent from the response.
        // Just focus on errors division upon load event.
        $response->getFooter()->getScripts()->addCode($jsCode);
    }
}
