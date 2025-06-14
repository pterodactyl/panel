<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class ShippingInfo
 *
 * Shipping information for the invoice recipient.
 *
 * @package PayPal\Api
 *
 * @property string first_name
 * @property string last_name
 * @property string business_name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Phone phone
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\InvoiceAddress address
 */
class ShippingInfo extends PayPalModel
{
    /**
     * First name of the invoice recipient. 30 characters max.
     *
     * @param string $first_name
     *
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * First name of the invoice recipient. 30 characters max.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Last name of the invoice recipient. 30 characters max.
     *
     * @param string $last_name
     *
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Last name of the invoice recipient. 30 characters max.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Company business name of the invoice recipient. 100 characters max.
     *
     * @param string $business_name
     *
     * @return $this
     */
    public function setBusinessName($business_name)
    {
        $this->business_name = $business_name;
        return $this;
    }

    /**
     * Company business name of the invoice recipient. 100 characters max.
     *
     * @return string
     */
    public function getBusinessName()
    {
        return $this->business_name;
    }

    /**
     *
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Phone $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     *
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     *
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Address of the invoice recipient.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\InvoiceAddress $address
     *
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Address of the invoice recipient.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\InvoiceAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

}
