<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

/**
 * Class Transaction
 *
 * A transaction defines the contract of a payment - what is the payment for and who is fulfilling it.
 *
 * @package PayPal\Api
 *
 */
class Transaction extends TransactionBase
{
    /**
     * Additional transactions for complex payment scenarios.
     *
     *
     * @param self $transactions
     *
     * @return $this
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
        return $this;
    }

    /**
     * Additional transactions for complex payment scenarios.
     *
     * @return self[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Identifier to the purchase unit corresponding to this sale transaction
     *
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
     * Identifier to the purchase unit corresponding to this sale transaction
     *
     * @return string
     */
    public function getPurchaseUnitReferenceId()
    {
        return $this->purchase_unit_reference_id;
    }

}
