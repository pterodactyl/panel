<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentOptions
 *
 * Payment options requested for this purchase unit
 *
 * @package PayPal\Api
 *
 * @property string allowed_payment_method
 */
class PaymentOptions extends PayPalModel
{
    /**
     * Optional payment method type. If specified, the transaction will go through for only instant payment. Only for use with the paypal payment_method, not relevant for the credit_card payment_method.
     * Valid Values: ["UNRESTRICTED", "INSTANT_FUNDING_SOURCE", "IMMEDIATE_PAY"]
     *
     * @param string $allowed_payment_method
     *
     * @return $this
     */
    public function setAllowedPaymentMethod($allowed_payment_method)
    {
        $this->allowed_payment_method = $allowed_payment_method;
        return $this;
    }

    /**
     * Optional payment method type. If specified, the transaction will go through for only instant payment. Only for use with the paypal payment_method, not relevant for the credit_card payment_method.
     *
     * @return string
     */
    public function getAllowedPaymentMethod()
    {
        return $this->allowed_payment_method;
    }

    /**
     * Indicator if this payment request is a recurring payment. Only supported when the `payment_method` is set to `credit_card`
     *
     * @deprecated Not publicly available
     * @param bool $recurring_flag
     *
     * @return $this
     */
    public function setRecurringFlag($recurring_flag)
    {
        $this->recurring_flag = $recurring_flag;
        return $this;
    }

    /**
     * Indicator if this payment request is a recurring payment. Only supported when the `payment_method` is set to `credit_card`
     *
     * @deprecated Not publicly available
     * @return bool
     */
    public function getRecurringFlag()
    {
        return $this->recurring_flag;
    }

    /**
     * Indicator if fraud management filters (fmf) should be skipped for this transaction. Only supported when the `payment_method` is set to `credit_card`
     *
     * @deprecated Not publicly available
     * @param bool $skip_fmf
     *
     * @return $this
     */
    public function setSkipFmf($skip_fmf)
    {
        $this->skip_fmf = $skip_fmf;
        return $this;
    }

    /**
     * Indicator if fraud management filters (fmf) should be skipped for this transaction. Only supported when the `payment_method` is set to `credit_card`
     *
     * @deprecated Not publicly available
     * @return bool
     */
    public function getSkipFmf()
    {
        return $this->skip_fmf;
    }

}
