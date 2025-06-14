<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class AlternatePayment
 *
 * A resource representing a alternate payment account that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string alternate_payment_account_id
 * @property string external_customer_id
 * @property string alternate_payment_provider_id
 */
class AlternatePayment extends PayPalModel
{
    /**
     * The unique identifier of the alternate payment account.
     *
     * @param string $alternate_payment_account_id
     *
     * @return $this
     */
    public function setAlternatePaymentAccountId($alternate_payment_account_id)
    {
        $this->alternate_payment_account_id = $alternate_payment_account_id;
        return $this;
    }

    /**
     * The unique identifier of the alternate payment account.
     *
     * @return string
     */
    public function getAlternatePaymentAccountId()
    {
        return $this->alternate_payment_account_id;
    }

    /**
     * The unique identifier of the payer
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
     * The unique identifier of the payer
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

    /**
     * Alternate Payment provider id. This is an optional attribute needed only for certain alternate providers e.g Ideal
     *
     * @param string $alternate_payment_provider_id
     *
     * @return $this
     */
    public function setAlternatePaymentProviderId($alternate_payment_provider_id)
    {
        $this->alternate_payment_provider_id = $alternate_payment_provider_id;
        return $this;
    }

    /**
     * Alternate Payment provider id. This is an optional attribute needed only for certain alternate providers e.g Ideal
     *
     * @return string
     */
    public function getAlternatePaymentProviderId()
    {
        return $this->alternate_payment_provider_id;
    }

}
