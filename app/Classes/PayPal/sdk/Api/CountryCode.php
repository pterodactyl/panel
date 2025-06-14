<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CountryCode
 *
 * Representation of a country code.
 *
 * @package PayPal\Api
 *
 * @property string country_code
 */
class CountryCode extends PayPalModel
{
    /**
     * ISO country code based on 2-character IS0-3166-1 codes.
     *
     * @param string $country_code
     *
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * ISO country code based on 2-character IS0-3166-1 codes.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

}
