<?php

namespace Facebook\WebDriver\Exception;

/**
 * Occurs if the given session id is not in the list of active sessions, meaning the session either does not exist
 * or that it’s not active.
 */
class InvalidSessionIdException extends WebDriverException
{
}
