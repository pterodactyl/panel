<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class Terms
 *
 * Resource representing terms used by the plan.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string type
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency max_billing_amount
 * @property string occurrences
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency amount_range
 * @property string buyer_editable
 */
class Terms extends PayPalModel
{
    /**
     * Identifier of the terms. 128 characters max.
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
     * Identifier of the terms. 128 characters max.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Term type. Allowed values: `MONTHLY`, `WEEKLY`, `YEARLY`.
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
     * Term type. Allowed values: `MONTHLY`, `WEEKLY`, `YEARLY`.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Max Amount associated with this term.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $max_billing_amount
     *
     * @return $this
     */
    public function setMaxBillingAmount($max_billing_amount)
    {
        $this->max_billing_amount = $max_billing_amount;
        return $this;
    }

    /**
     * Max Amount associated with this term.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getMaxBillingAmount()
    {
        return $this->max_billing_amount;
    }

    /**
     * How many times money can be pulled during this term.
     *
     * @param string $occurrences
     *
     * @return $this
     */
    public function setOccurrences($occurrences)
    {
        $this->occurrences = $occurrences;
        return $this;
    }

    /**
     * How many times money can be pulled during this term.
     *
     * @return string
     */
    public function getOccurrences()
    {
        return $this->occurrences;
    }

    /**
     * Amount_range associated with this term.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $amount_range
     *
     * @return $this
     */
    public function setAmountRange($amount_range)
    {
        $this->amount_range = $amount_range;
        return $this;
    }

    /**
     * Amount_range associated with this term.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmountRange()
    {
        return $this->amount_range;
    }

    /**
     * Buyer's ability to edit the amount in this term.
     *
     * @param string $buyer_editable
     *
     * @return $this
     */
    public function setBuyerEditable($buyer_editable)
    {
        $this->buyer_editable = $buyer_editable;
        return $this;
    }

    /**
     * Buyer's ability to edit the amount in this term.
     *
     * @return string
     */
    public function getBuyerEditable()
    {
        return $this->buyer_editable;
    }

}
