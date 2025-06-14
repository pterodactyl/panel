<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Phone
 *
 * Information related to the Payer. In case of PayPal Wallet payment, this information will be filled in by PayPal after the user approves the payment using their PayPal Wallet.
 *
 * @package PayPal\Api
 *
 * @property string country_code
 * @property string national_number
 * @property string extension
 */
class Phone extends PayPalModel
{
    /**
     * Country code (from in E.164 format)
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
     * Country code (from in E.164 format)
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * In-country phone number (from in E.164 format)
     *
     * @param string $national_number
     *
     * @return $this
     */
    public function setNationalNumber($national_number)
    {
        $this->national_number = $national_number;
        return $this;
    }

    /**
     * In-country phone number (from in E.164 format)
     *
     * @return string
     */
    public function getNationalNumber()
    {
        return $this->national_number;
    }

    /**
     * Phone extension
     *
     * @param string $extension
     *
     * @return $this
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
        return $this;
    }

    /**
     * Phone extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

}
