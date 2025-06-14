<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class FundingDetail
 *
 * Additional detail of the funding.
 *
 * @package PayPal\Api
 *
 * @property string clearing_time
 * @property string payment_hold_date
 * @property string payment_debit_date
 * @property string processing_type
 */
class FundingDetail extends PayPalModel
{
    /**
     * Expected clearing time
     *
     * @param string $clearing_time
     *
     * @return $this
     */
    public function setClearingTime($clearing_time)
    {
        $this->clearing_time = $clearing_time;
        return $this;
    }

    /**
     * Expected clearing time
     *
     * @return string
     */
    public function getClearingTime()
    {
        return $this->clearing_time;
    }

    /**
     * [DEPRECATED] Hold-off duration of the payment. payment_debit_date should be used instead.
     *
     * @param string $payment_hold_date
     *
     * @return $this
     */
    public function setPaymentHoldDate($payment_hold_date)
    {
        $this->payment_hold_date = $payment_hold_date;
        return $this;
    }

    /**
     * @deprecated  [DEPRECATED] Hold-off duration of the payment. payment_debit_date should be used instead.
     *
     * @return string
     */
    public function getPaymentHoldDate()
    {
        return $this->payment_hold_date;
    }

    /**
     * Date when funds will be debited from the payer's account
     *
     * @param string $payment_debit_date
     *
     * @return $this
     */
    public function setPaymentDebitDate($payment_debit_date)
    {
        $this->payment_debit_date = $payment_debit_date;
        return $this;
    }

    /**
     * Date when funds will be debited from the payer's account
     *
     * @return string
     */
    public function getPaymentDebitDate()
    {
        return $this->payment_debit_date;
    }

    /**
     * Processing type of the payment card
     * Valid Values: ["PINLESS_DEBIT"]
     *
     * @param string $processing_type
     *
     * @return $this
     */
    public function setProcessingType($processing_type)
    {
        $this->processing_type = $processing_type;
        return $this;
    }

    /**
     * Processing type of the payment card
     *
     * @return string
     */
    public function getProcessingType()
    {
        return $this->processing_type;
    }

}
