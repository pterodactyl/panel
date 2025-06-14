<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class MerchantInfo
 *
 * Business information of the merchant that will appear on the invoice.
 *
 * @package PayPal\Api
 *
 * @property string email
 * @property string first_name
 * @property string last_name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\InvoiceAddress address
 * @property string business_name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Phone phone
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Phone fax
 * @property string website
 * @property string tax_id
 * @property string additional_info
 */
class MerchantInfo extends PayPalModel
{
    /**
     * Email address of the merchant. 260 characters max.
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Email address of the merchant. 260 characters max.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * First name of the merchant. 30 characters max.
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
     * First name of the merchant. 30 characters max.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Last name of the merchant. 30 characters max.
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
     * Last name of the merchant. 30 characters max.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Address of the merchant.
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
     * Address of the merchant.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\InvoiceAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Company business name of the merchant. 100 characters max.
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
     * Company business name of the merchant. 100 characters max.
     *
     * @return string
     */
    public function getBusinessName()
    {
        return $this->business_name;
    }

    /**
     * Phone number of the merchant.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Phone $phone
     *
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Phone number of the merchant.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Fax number of the merchant.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Phone $fax
     *
     * @return $this
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
        return $this;
    }

    /**
     * Fax number of the merchant.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Phone
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Website of the merchant. 2048 characters max.
     *
     * @param string $website
     *
     * @return $this
     */
    public function setWebsite($website)
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Website of the merchant. 2048 characters max.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Tax ID of the merchant. 100 characters max.
     *
     * @param string $tax_id
     *
     * @return $this
     */
    public function setTaxId($tax_id)
    {
        $this->tax_id = $tax_id;
        return $this;
    }

    /**
     * Tax ID of the merchant. 100 characters max.
     *
     * @return string
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * Option to display additional information such as business hours. 40 characters max.
     *
     * @param string $additional_info
     *
     * @return $this
     */
    public function setAdditionalInfo($additional_info)
    {
        $this->additional_info = $additional_info;
        return $this;
    }

    /**
     * Option to display additional information such as business hours. 40 characters max.
     *
     * @return string
     */
    public function getAdditionalInfo()
    {
        return $this->additional_info;
    }

}
