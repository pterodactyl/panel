<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;
use Pterodactyl\Classes\PayPal\sdk\Validation\UrlValidator;

/**
 * Class MerchantPreferences
 *
 * Resource representing merchant preferences like max failed attempts, set up fee  and others for a plan.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency setup_fee
 * @property string cancel_url
 * @property string return_url
 * @property string notify_url
 * @property string max_fail_attempts
 * @property string auto_bill_amount
 * @property string initial_fail_amount_action
 * @property string accepted_payment_type
 * @property string char_set
 */
class MerchantPreferences extends PayPalModel
{
    /**
     * Identifier of the merchant_preferences. 128 characters max.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Identifier of the merchant_preferences. 128 characters max.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setup fee amount. Default is 0.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $setup_fee
     *
     * @return $this
     */
    public function setSetupFee($setup_fee)
    {
        $this->setup_fee = $setup_fee;
        return $this;
    }

    /**
     * Setup fee amount. Default is 0.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getSetupFee()
    {
        return $this->setup_fee;
    }

    /**
     * Redirect URL on cancellation of agreement request. 1000 characters max.
     *
     * @param string $cancel_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setCancelUrl($cancel_url)
    {
        UrlValidator::validate($cancel_url, "CancelUrl");
        $this->cancel_url = $cancel_url;
        return $this;
    }

    /**
     * Redirect URL on cancellation of agreement request. 1000 characters max.
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->cancel_url;
    }

    /**
     * Redirect URL on creation of agreement request. 1000 characters max.
     *
     * @param string $return_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setReturnUrl($return_url)
    {
        UrlValidator::validate($return_url, "ReturnUrl");
        $this->return_url = $return_url;
        return $this;
    }

    /**
     * Redirect URL on creation of agreement request. 1000 characters max.
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->return_url;
    }

    /**
     * Notify URL on agreement creation. 1000 characters max.
     *
     * @param string $notify_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setNotifyUrl($notify_url)
    {
        UrlValidator::validate($notify_url, "NotifyUrl");
        $this->notify_url = $notify_url;
        return $this;
    }

    /**
     * Notify URL on agreement creation. 1000 characters max.
     *
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notify_url;
    }

    /**
     * Total number of failed attempts allowed. Default is 0, representing an infinite number of failed attempts.
     *
     * @param string $max_fail_attempts
     *
     * @return $this
     */
    public function setMaxFailAttempts($max_fail_attempts)
    {
        $this->max_fail_attempts = $max_fail_attempts;
        return $this;
    }

    /**
     * Total number of failed attempts allowed. Default is 0, representing an infinite number of failed attempts.
     *
     * @return string
     */
    public function getMaxFailAttempts()
    {
        return $this->max_fail_attempts;
    }

    /**
     * Allow auto billing for the outstanding amount of the agreement in the next cycle. Allowed values: `YES`, `NO`. Default is `NO`.
     *
     * @param string $auto_bill_amount
     *
     * @return $this
     */
    public function setAutoBillAmount($auto_bill_amount)
    {
        $this->auto_bill_amount = $auto_bill_amount;
        return $this;
    }

    /**
     * Allow auto billing for the outstanding amount of the agreement in the next cycle. Allowed values: `YES`, `NO`. Default is `NO`.
     *
     * @return string
     */
    public function getAutoBillAmount()
    {
        return $this->auto_bill_amount;
    }

    /**
     * Action to take if a failure occurs during initial payment. Allowed values: `CONTINUE`, `CANCEL`. Default is continue.
     *
     * @param string $initial_fail_amount_action
     *
     * @return $this
     */
    public function setInitialFailAmountAction($initial_fail_amount_action)
    {
        $this->initial_fail_amount_action = $initial_fail_amount_action;
        return $this;
    }

    /**
     * Action to take if a failure occurs during initial payment. Allowed values: `CONTINUE`, `CANCEL`. Default is continue.
     *
     * @return string
     */
    public function getInitialFailAmountAction()
    {
        return $this->initial_fail_amount_action;
    }

    /**
     * Payment types that are accepted for this plan.
     *
     * @param string $accepted_payment_type
     *
     * @return $this
     */
    public function setAcceptedPaymentType($accepted_payment_type)
    {
        $this->accepted_payment_type = $accepted_payment_type;
        return $this;
    }

    /**
     * Payment types that are accepted for this plan.
     *
     * @return string
     */
    public function getAcceptedPaymentType()
    {
        return $this->accepted_payment_type;
    }

    /**
     * char_set for this plan.
     *
     * @param string $char_set
     *
     * @return $this
     */
    public function setCharSet($char_set)
    {
        $this->char_set = $char_set;
        return $this;
    }

    /**
     * char_set for this plan.
     *
     * @return string
     */
    public function getCharSet()
    {
        return $this->char_set;
    }

}
