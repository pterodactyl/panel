<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentCardToken
 *
 * A resource representing a payment card that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string payment_card_id
 * @property string external_customer_id
 * @property string last4
 * @property string type
 * @property int expire_month
 * @property int expire_year
 */
class PaymentCardToken extends PayPalModel
{
    /**
     * ID of a previously saved Payment Card resource.
     *
     * @param string $payment_card_id
     *
     * @return $this
     */
    public function setPaymentCardId($payment_card_id)
    {
        $this->payment_card_id = $payment_card_id;
        return $this;
    }

    /**
     * ID of a previously saved Payment Card resource.
     *
     * @return string
     */
    public function getPaymentCardId()
    {
        return $this->payment_card_id;
    }

    /**
     * The unique identifier of the payer used when saving this payment card.
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
     * The unique identifier of the payer used when saving this payment card.
     *
     * @return string
     */
    public function getExternalCustomerId()
    {
        return $this->external_customer_id;
    }

    /**
     * Last 4 digits of the card number from the saved card.
     *
     * @param string $last4
     *
     * @return $this
     */
    public function setLast4($last4)
    {
        $this->last4 = $last4;
        return $this;
    }

    /**
     * Last 4 digits of the card number from the saved card.
     *
     * @return string
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * Type of the Card.
     * Valid Values: ["VISA", "AMEX", "SOLO", "JCB", "STAR", "DELTA", "DISCOVER", "SWITCH", "MAESTRO", "CB_NATIONALE", "CONFINOGA", "COFIDIS", "ELECTRON", "CETELEM", "CHINA_UNION_PAY", "MASTERCARD"]
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Type of the Card.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Expiry month from the saved card with value 1 - 12.
     *
     * @param int $expire_month
     *
     * @return $this
     */
    public function setExpireMonth($expire_month)
    {
        $this->expire_month = $expire_month;
        return $this;
    }

    /**
     * Expiry month from the saved card with value 1 - 12.
     *
     * @return int
     */
    public function getExpireMonth()
    {
        return $this->expire_month;
    }

    /**
     * Four digit expiry year from the saved card, represented as YYYY format.
     *
     * @param int $expire_year
     *
     * @return $this
     */
    public function setExpireYear($expire_year)
    {
        $this->expire_year = $expire_year;
        return $this;
    }

    /**
     * Four digit expiry year from the saved card, represented as YYYY format.
     *
     * @return int
     */
    public function getExpireYear()
    {
        return $this->expire_year;
    }

}
