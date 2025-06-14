<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class BillingInfo
 *
 * Billing information for the invoice recipient.
 *
 * @package PayPal\Api
 *
 * @property string email
 * @property string first_name
 * @property string last_name
 * @property string business_name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\InvoiceAddress address
 * @property string language
 * @property string additional_info
 * @property string notification_channel
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Phone phone
 */
class BillingInfo extends PayPalModel
{
    /**
     * Email address of the invoice recipient. 260 characters max.
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
     * Email address of the invoice recipient. 260 characters max.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

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

    /**
     * Language of the email sent to the payer. Will only be used if payer doesn't have a PayPal account.
     * Valid Values: ["da_DK", "de_DE", "en_AU", "en_GB", "en_US", "es_ES", "es_XC", "fr_CA", "fr_FR", "fr_XC", "he_IL", "id_ID", "it_IT", "ja_JP", "nl_NL", "no_NO", "pl_PL", "pt_BR", "pt_PT", "ru_RU", "sv_SE", "th_TH", "tr_TR", "zh_CN", "zh_HK", "zh_TW", "zh_XC"]
     *
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Language of the email sent to the payer. Will only be used if payer doesn't have a PayPal account.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
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

    /**
     * Preferred notification channel of the payer. Email by default.
     * Valid Values: ["SMS", "EMAIL"]
     *
     * @param string $notification_channel
     *
     * @return $this
     */
    public function setNotificationChannel($notification_channel)
    {
        $this->notification_channel = $notification_channel;
        return $this;
    }

    /**
     * Preferred notification channel of the payer. Email by default.
     *
     * @return string
     */
    public function getNotificationChannel()
    {
        return $this->notification_channel;
    }

    /**
     * Mobile Phone number of the recipient to which SMS will be sent if notification_channel is SMS.
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
     * Mobile Phone number of the recipient to which SMS will be sent if notification_channel is SMS.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

}
