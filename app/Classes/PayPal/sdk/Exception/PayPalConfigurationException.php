<?php

namespace Pterodactyl\Classes\PayPal\sdk\Exception;

/**
 * Class PayPalConfigurationException
 *
 * @package PayPal\Exception
 */
class PayPalConfigurationException extends \Exception
{

    /**
     * Default Constructor
     *
     * @param string|null $message
     * @param int  $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}
