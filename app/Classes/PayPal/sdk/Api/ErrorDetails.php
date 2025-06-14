<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class ErrorDetails
 *
 * Details about a specific error.
 *
 * @package PayPal\Api
 *
 * @property string field
 * @property string issue
 */
class ErrorDetails extends PayPalModel
{
    /**
     * Name of the field that caused the error.
     *
     * @param string $field
     *
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Name of the field that caused the error.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Reason for the error.
     *
     * @param string $issue
     *
     * @return $this
     */
    public function setIssue($issue)
    {
        $this->issue = $issue;
        return $this;
    }

    /**
     * Reason for the error.
     *
     * @return string
     */
    public function getIssue()
    {
        return $this->issue;
    }

    /**
     * Reference ID of the purchase_unit associated with this error
     *
     * @deprecated Not publicly available
     * @param string $purchase_unit_reference_id
     *
     * @return $this
     */
    public function setPurchaseUnitReferenceId($purchase_unit_reference_id)
    {
        $this->purchase_unit_reference_id = $purchase_unit_reference_id;
        return $this;
    }

    /**
     * Reference ID of the purchase_unit associated with this error
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getPurchaseUnitReferenceId()
    {
        return $this->purchase_unit_reference_id;
    }

    /**
     * PayPal internal error code.
     *
     * @deprecated Not publicly available
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * PayPal internal error code.
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

}
