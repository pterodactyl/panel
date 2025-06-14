<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentDetail
 *
 * Invoicing payment information.
 *
 * @package PayPal\Api
 *
 * @property string type
 * @property string transaction_id
 * @property string transaction_type
 * @property string date
 * @property string method
 * @property string note
 */
class PaymentDetail extends PayPalModel
{
    /**
     * PayPal payment detail indicating whether payment was made in an invoicing flow via PayPal or externally. In the case of the mark-as-paid API, payment type is EXTERNAL and this is what is now supported. The PAYPAL value is provided for backward compatibility.
     * Valid Values: ["PAYPAL", "EXTERNAL"]
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
     * PayPal payment detail indicating whether payment was made in an invoicing flow via PayPal or externally. In the case of the mark-as-paid API, payment type is EXTERNAL and this is what is now supported. The PAYPAL value is provided for backward compatibility.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * PayPal payment transaction id. Mandatory field in case the type value is PAYPAL.
     *
     * @param string $transaction_id
     *
     * @return $this
     */
    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    /**
     * PayPal payment transaction id. Mandatory field in case the type value is PAYPAL.
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Type of the transaction.
     * Valid Values: ["SALE", "AUTHORIZATION", "CAPTURE"]
     *
     * @param string $transaction_type
     *
     * @return $this
     */
    public function setTransactionType($transaction_type)
    {
        $this->transaction_type = $transaction_type;
        return $this;
    }

    /**
     * Type of the transaction.
     *
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transaction_type;
    }

    /**
     * Date when the invoice was paid. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Date when the invoice was paid. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Payment mode or method. This field is mandatory if the value of the type field is EXTERNAL.
     * Valid Values: ["BANK_TRANSFER", "CASH", "CHECK", "CREDIT_CARD", "DEBIT_CARD", "PAYPAL", "WIRE_TRANSFER", "OTHER"]
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Payment mode or method. This field is mandatory if the value of the type field is EXTERNAL.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Optional note associated with the payment.
     *
     * @param string $note
     *
     * @return $this
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    /**
     * Optional note associated with the payment.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

}
