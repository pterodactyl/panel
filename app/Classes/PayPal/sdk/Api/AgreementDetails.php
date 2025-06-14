<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class AgreementDetails
 *
 * A resource representing the agreement details.
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency outstanding_balance
 * @property string cycles_remaining
 * @property string cycles_completed
 * @property string next_billing_date
 * @property string last_payment_date
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency last_payment_amount
 * @property string final_payment_date
 * @property string failed_payment_count
 */
class AgreementDetails extends PayPalModel
{
    /**
     * The outstanding balance for this agreement.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $outstanding_balance
     *
     * @return $this
     */
    public function setOutstandingBalance($outstanding_balance)
    {
        $this->outstanding_balance = $outstanding_balance;
        return $this;
    }

    /**
     * The outstanding balance for this agreement.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getOutstandingBalance()
    {
        return $this->outstanding_balance;
    }

    /**
     * Number of cycles remaining for this agreement.
     *
     * @param string $cycles_remaining
     *
     * @return $this
     */
    public function setCyclesRemaining($cycles_remaining)
    {
        $this->cycles_remaining = $cycles_remaining;
        return $this;
    }

    /**
     * Number of cycles remaining for this agreement.
     *
     * @return string
     */
    public function getCyclesRemaining()
    {
        return $this->cycles_remaining;
    }

    /**
     * Number of cycles completed for this agreement.
     *
     * @param string $cycles_completed
     *
     * @return $this
     */
    public function setCyclesCompleted($cycles_completed)
    {
        $this->cycles_completed = $cycles_completed;
        return $this;
    }

    /**
     * Number of cycles completed for this agreement.
     *
     * @return string
     */
    public function getCyclesCompleted()
    {
        return $this->cycles_completed;
    }

    /**
     * The next billing date for this agreement, represented as 2014-02-19T10:00:00Z format.
     *
     * @param string $next_billing_date
     *
     * @return $this
     */
    public function setNextBillingDate($next_billing_date)
    {
        $this->next_billing_date = $next_billing_date;
        return $this;
    }

    /**
     * The next billing date for this agreement, represented as 2014-02-19T10:00:00Z format.
     *
     * @return string
     */
    public function getNextBillingDate()
    {
        return $this->next_billing_date;
    }

    /**
     * Last payment date for this agreement, represented as 2014-06-09T09:42:31Z format.
     *
     * @param string $last_payment_date
     *
     * @return $this
     */
    public function setLastPaymentDate($last_payment_date)
    {
        $this->last_payment_date = $last_payment_date;
        return $this;
    }

    /**
     * Last payment date for this agreement, represented as 2014-06-09T09:42:31Z format.
     *
     * @return string
     */
    public function getLastPaymentDate()
    {
        return $this->last_payment_date;
    }

    /**
     * Last payment amount for this agreement.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $last_payment_amount
     *
     * @return $this
     */
    public function setLastPaymentAmount($last_payment_amount)
    {
        $this->last_payment_amount = $last_payment_amount;
        return $this;
    }

    /**
     * Last payment amount for this agreement.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getLastPaymentAmount()
    {
        return $this->last_payment_amount;
    }

    /**
     * Last payment date for this agreement, represented as 2015-02-19T10:00:00Z format.
     *
     * @param string $final_payment_date
     *
     * @return $this
     */
    public function setFinalPaymentDate($final_payment_date)
    {
        $this->final_payment_date = $final_payment_date;
        return $this;
    }

    /**
     * Last payment date for this agreement, represented as 2015-02-19T10:00:00Z format.
     *
     * @return string
     */
    public function getFinalPaymentDate()
    {
        return $this->final_payment_date;
    }

    /**
     * Total number of failed payments for this agreement.
     *
     * @param string $failed_payment_count
     *
     * @return $this
     */
    public function setFailedPaymentCount($failed_payment_count)
    {
        $this->failed_payment_count = $failed_payment_count;
        return $this;
    }

    /**
     * Total number of failed payments for this agreement.
     *
     * @return string
     */
    public function getFailedPaymentCount()
    {
        return $this->failed_payment_count;
    }

}
