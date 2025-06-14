<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CarrierAccount
 *
 * Payment Instrument that facilitates carrier billing
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string phone_number
 * @property string external_customer_id
 * @property string phone_source
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\CountryCode country_code
 */
class CarrierAccount extends PayPalModel
{
    /**
     * ID that identifies the payer�s carrier account. Can be used in subsequent REST API calls, e.g. for making payments.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * ID that identifies the payer�s carrier account. Can be used in subsequent REST API calls, e.g. for making payments.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * The payer�s phone number in E.164 format.
     *
     * @param string $phone_number
     *
     * @return $this
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    /**
     * The payer�s phone number in E.164 format.
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * User identifier as created by the merchant.
     *
     * @param string $external_customer_id
     *
     * @return $this
     */
    public function setExternalCustomerId($external_customer_id)
    {
        $this->external_customer_id = $external_customer_id;
        return $this;
    }

    /**
     * User identifier as created by the merchant.
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

    /**
     * The method of obtaining the phone number (USER_PROVIDED or READ_FROM_DEVICE).
     * Valid Values: ["READ_FROM_DEVICE", "USER_PROVIDED"]
     *
     * @param string $phone_source
     *
     * @return $this
     */
    public function setPhoneSource($phone_source)
    {
        $this->phone_source = $phone_source;
        return $this;
    }

    /**
     * The method of obtaining the phone number (USER_PROVIDED or READ_FROM_DEVICE).
     *
     * @return string
     */
    public function getPhoneSource()
    {
        return $this->phone_source;
    }

    /**
     * The country where the phone number is registered. Specified in 2-character IS0-3166-1 format.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\CountryCode $country_code
     *
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * The country where the phone number is registered. Specified in 2-character IS0-3166-1 format.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\CountryCode
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

}
