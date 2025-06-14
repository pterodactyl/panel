<?php

namespace Pterodactyl\Classes\PayPal\sdk\Exception;

/**
 * Class PayPalMissingCredentialException
 *
 * @package PayPal\Exception
 */
class PayPalMissingCredentialException extends \Exception
{

    /**
     * Default Constructor
     *
     * @param string $message
     * @param int  $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * prints error message
     *
     * @return string
     */
    public function errorMessage()
    {
        $errorMsg = 'Error on line ' . $this->getLine() . ' in ' . $this->getFile()
            . ': <b>' . $this->getMessage() . '</b>';

        return $errorMsg;
    }

}
