<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Converter\FormatConverter;
use Pterodactyl\Classes\PayPal\sdk\Validation\NumericValidator;

/**
 * Class Amount
 *
 * payment amount with break-ups.
 *
 * @package PayPal\Api
 *
 * @property string              currency
 * @property string              total
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Details details
 */
class Amount extends PayPalModel
{
    /**
     * 3-letter [currency code](https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/). PayPal does not support all currencies.
     *
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * 3-letter [currency code](https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/). PayPal does not support all currencies.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Total amount charged from the payer to the payee. In case of a refund, this is the refunded amount to the original payer from the payee. 10 characters max with support for 2 decimal places.
     *
     * @param string|double $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        NumericValidator::validate($total, "Total");
        $total = FormatConverter::formatToPrice($total, $this->getCurrency());
        $this->total = $total;
        return $this;
    }

    /**
     * Total amount charged from the payer to the payee. In case of a refund, this is the refunded amount to the original payer from the payee. 10 characters max with support for 2 decimal places.
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Additional details of the payment amount.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Details $details
     *
     * @return $this
     */
    public function setDetails($details)
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Additional details of the payment amount.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Details
     */
    public function getDetails()
    {
        return $this->details;
    }

}
