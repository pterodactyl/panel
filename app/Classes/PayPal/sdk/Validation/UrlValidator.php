<?php

namespace Pterodactyl\Classes\PayPal\sdk\Validation;

/**
 * Class UrlValidator
 *
 * @package PayPal\Validation
 */
class UrlValidator
{

    /**
     * Helper method for validating URLs that will be used by this API in any requests.
     *
     * @param      $url
     * @param string|null $urlName
     * @throws \InvalidArgumentException
     */
    public static function validate($url, $urlName = null)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException("$urlName is not a fully qualified URL");
        }
    }
}
