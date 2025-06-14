<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PaymentTerm
 *
 * Payment term of the invoice. If term_type is present, due_date must not be present and vice versa.
 *
 * @package PayPal\Api
 *
 * @property string term_type
 * @property string due_date
 */
class PaymentTerm extends PayPalModel
{
    /**
     * Terms by which the invoice payment is due.
     * Valid Values: ["DUE_ON_RECEIPT", "NET_10", "NET_15", "NET_30", "NET_45"]
     *
     * @param string $term_type
     *
     * @return $this
     */
    public function setTermType($term_type)
    {
        $this->term_type = $term_type;
        return $this;
    }

    /**
     * Terms by which the invoice payment is due.
     *
     * @return string
     */
    public function getTermType()
    {
        return $this->term_type;
    }

    /**
     * Date on which invoice payment is due. It must be always a future date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $due_date
     *
     * @return $this
     */
    public function setDueDate($due_date)
    {
        $this->due_date = $due_date;
        return $this;
    }

    /**
     * Date on which invoice payment is due. It must be always a future date. Date format yyyy-MM-dd z, as defined in [ISO8601](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getDueDate()
    {
        return $this->due_date;
    }

}
