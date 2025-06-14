<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PotentialPayerInfo
 *
 * A resource representing a information about a potential Payer.
 *
 * @package PayPal\Api
 *
 * @property string email
 * @property string external_remember_me_id
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Address billing_address
 */
class PotentialPayerInfo extends PayPalModel
{
    /**
     * Email address representing the potential payer.
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
     * Email address representing the potential payer.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * ExternalRememberMe id representing the potential payer
     *
     * @param string $external_remember_me_id
     *
     * @return $this
     */
    public function setExternalRememberMeId($external_remember_me_id)
    {
        $this->external_remember_me_id = $external_remember_me_id;
        return $this;
    }

    /**
     * ExternalRememberMe id representing the potential payer
     *
     * @return string
     */
    public function getExternalRememberMeId()
    {
        return $this->external_remember_me_id;
    }

    /**
     * Account Number representing the potential payer
     * @deprecated Not publicly available
     * @param string $account_number
     *
     * @return $this
     */
    public function setAccountNumber($account_number)
    {
        $this->account_number = $account_number;
        return $this;
    }

    /**
     * Account Number representing the potential payer
     * @deprecated Not publicly available
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    /**
     * Billing address of the potential payer.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Address $billing_address
     *
     * @return $this
     */
    public function setBillingAddress($billing_address)
    {
        $this->billing_address = $billing_address;
        return $this;
    }

    /**
     * Billing address of the potential payer.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Address
     */
    public function getBillingAddress()
    {
        return $this->billing_address;
    }

}
