<?php

namespace Facebook\WebDriver\Exception;

use Exception;

/**
 * @see https://w3c.github.io/webdriver/#errors
 */
class WebDriverException extends Exception
{
    private $results;

    /**
     * @param string $message
     * @param mixed $results
     */
    public function __construct($message, $results = null)
    {
        parent::__construct($message);
        $this->results = $results;
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Throw WebDriverExceptions based on WebDriver status code.
     *
     * @param int|string $status_code
     * @param string $message
     * @param mixed $results
     *
     * @throws ElementClickInterceptedException
     * @throws ElementNotInteractableException
     * @throws ElementNotSelectableException
     * @throws ElementNotVisibleException
     * @throws ExpectedException
     * @throws IMEEngineActivationFailedException
     * @throws IMENotAvailableException
     * @throws IndexOutOfBoundsException
     * @throws InsecureCertificateException
     * @throws InvalidArgumentException
     * @throws InvalidCookieDomainException
     * @throws InvalidCoordinatesException
     * @throws InvalidElementStateException
     * @throws InvalidSelectorException
     * @throws InvalidSessionIdException
     * @throws JavascriptErrorException
     * @throws MoveTargetOutOfBoundsException
     * @throws NoAlertOpenException
     * @throws NoCollectionException
     * @throws NoScriptResultException
     * @throws NoStringException
     * @throws NoStringLengthException
     * @throws NoStringWrapperException
     * @throws NoSuchAlertException
     * @throws NoSuchCollectionException
     * @throws NoSuchCookieException
     * @throws NoSuchDocumentException
     * @throws NoSuchDriverException
     * @throws NoSuchElementException
     * @throws NoSuchFrameException
     * @throws NoSuchWindowException
     * @throws NullPointerException
     * @throws ScriptTimeoutException
     * @throws SessionNotCreatedException
     * @throws StaleElementReferenceException
     * @throws TimeoutException
     * @throws UnableToCaptureScreenException
     * @throws UnableToSetCookieException
     * @throws UnexpectedAlertOpenException
     * @throws UnexpectedJavascriptException
     * @throws UnknownCommandException
     * @throws UnknownErrorException
     * @throws UnknownMethodException
     * @throws UnknownServerException
     * @throws UnrecognizedExceptionException
     * @throws UnsupportedOperationException
     * @throws XPathLookupException
     */
    public static function throwException($status_code, $message, $results)
    {
        if (is_string($status_code)) {
            // @see https://w3c.github.io/webdriver/#errors
            switch ($status_code) {
                case 'element click intercepted':
                    throw new ElementClickInterceptedException($message, $results);
                case 'element not interactable':
                    throw new ElementNotInteractableException($message, $results);
                case 'insecure certificate':
                    throw new InsecureCertificateException($message, $results);
                case 'invalid argument':
                    throw new InvalidArgumentException($message, $results);
                case 'invalid cookie domain':
                    throw new InvalidCookieDomainException($message, $results);
                case 'invalid element state':
                    throw new InvalidElementStateException($message, $results);
                case 'invalid selector':
                    throw new InvalidSelectorException($message, $results);
                case 'invalid session id':
                    throw new InvalidSessionIdException($message, $results);
                case 'javascript error':
                    throw new JavascriptErrorException($message, $results);
                case 'move target out of bounds':
                    throw new MoveTargetOutOfBoundsException($message, $results);
                case 'no such alert':
                    throw new NoSuchAlertException($message, $results);
                case 'no such cookie':
                    throw new NoSuchCookieException($message, $results);
                case 'no such element':
                    throw new NoSuchElementException($message, $results);
                case 'no such frame':
                    throw new NoSuchFrameException($message, $results);
                case 'no such window':
                    throw new NoSuchWindowException($message, $results);
                case 'no such shadow root':
                    throw new NoSuchShadowRootException($message, $results);
                case 'script timeout':
                    throw new ScriptTimeoutException($message, $results);
                case 'session not created':
                    throw new SessionNotCreatedException($message, $results);
                case 'stale element reference':
                    throw new StaleElementReferenceException($message, $results);
                case 'detached shadow root':
                    throw new DetachedShadowRootException($message, $results);
                case 'timeout':
                    throw new TimeoutException($message, $results);
                case 'unable to set cookie':
                    throw new UnableToSetCookieException($message, $results);
                case 'unable to capture screen':
                    throw new UnableToCaptureScreenException($message, $results);
                case 'unexpected alert open':
                    throw new UnexpectedAlertOpenException($message, $results);
                case 'unknown command':
                    throw new UnknownCommandException($message, $results);
                case 'unknown error':
                    throw new UnknownErrorException($message, $results);
                case 'unknown method':
                    throw new UnknownMethodException($message, $results);
                case 'unsupported operation':
                    throw new UnsupportedOperationException($message, $results);
                default:
                    throw new UnrecognizedExceptionException($message, $results);
            }
        }

        switch ($status_code) {
            case 1:
                throw new IndexOutOfBoundsException($message, $results);
            case 2:
                throw new NoCollectionException($message, $results);
            case 3:
                throw new NoStringException($message, $results);
            case 4:
                throw new NoStringLengthException($message, $results);
            case 5:
                throw new NoStringWrapperException($message, $results);
            case 6:
                throw new NoSuchDriverException($message, $results);
            case 7:
                throw new NoSuchElementException($message, $results);
            case 8:
                throw new NoSuchFrameException($message, $results);
            case 9:
                throw new UnknownCommandException($message, $results);
            case 10:
                throw new StaleElementReferenceException($message, $results);
            case 11:
                throw new ElementNotVisibleException($message, $results);
            case 12:
                throw new InvalidElementStateException($message, $results);
            case 13:
                throw new UnknownServerException($message, $results);
            case 14:
                throw new ExpectedException($message, $results);
            case 15:
                throw new ElementNotSelectableException($message, $results);
            case 16:
                throw new NoSuchDocumentException($message, $results);
            case 17:
                throw new UnexpectedJavascriptException($message, $results);
            case 18:
                throw new NoScriptResultException($message, $results);
            case 19:
                throw new XPathLookupException($message, $results);
            case 20:
                throw new NoSuchCollectionException($message, $results);
            case 21:
                throw new TimeoutException($message, $results);
            case 22:
                throw new NullPointerException($message, $results);
            case 23:
                throw new NoSuchWindowException($message, $results);
            case 24:
                throw new InvalidCookieDomainException($message, $results);
            case 25:
                throw new UnableToSetCookieException($message, $results);
            case 26:
                throw new UnexpectedAlertOpenException($message, $results);
            case 27:
                throw new NoAlertOpenException($message, $results);
            case 28:
                throw new ScriptTimeoutException($message, $results);
            case 29:
                throw new InvalidCoordinatesException($message, $results);
            case 30:
                throw new IMENotAvailableException($message, $results);
            case 31:
                throw new IMEEngineActivationFailedException($message, $results);
            case 32:
                throw new InvalidSelectorException($message, $results);
            case 33:
                throw new SessionNotCreatedException($message, $results);
            case 34:
                throw new MoveTargetOutOfBoundsException($message, $results);
            default:
                throw new UnrecognizedExceptionException($message, $results);
        }
    }
}
