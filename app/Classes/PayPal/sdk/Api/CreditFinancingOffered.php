<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CreditFinancingOffered
 *
 * Credit financing offered to customer on PayPal side with opt-in/opt-out status
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency total_cost
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\number term
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency monthly_payment
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency total_interest
 * @property bool payer_acceptance
 * @property bool cart_amount_immutable
 */
class CreditFinancingOffered extends PayPalModel
{
    /**
     * This is the estimated total payment amount including interest and fees the user will pay during the lifetime of the loan.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $total_cost
     *
     * @return $this
     */
    public function setTotalCost($total_cost)
    {
        $this->total_cost = $total_cost;
        return $this;
    }

    /**
     * This is the estimated total payment amount including interest and fees the user will pay during the lifetime of the loan.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getTotalCost()
    {
        return $this->total_cost;
    }

    /**
     * Length of financing terms in month
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\number $term
     *
     * @return $this
     */
    public function setTerm($term)
    {
        $this->term = $term;
        return $this;
    }

    /**
     * Length of financing terms in month
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\number
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * This is the estimated amount per month that the customer will need to pay including fees and interest.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $monthly_payment
     *
     * @return $this
     */
    public function setMonthlyPayment($monthly_payment)
    {
        $this->monthly_payment = $monthly_payment;
        return $this;
    }

    /**
     * This is the estimated amount per month that the customer will need to pay including fees and interest.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getMonthlyPayment()
    {
        return $this->monthly_payment;
    }

    /**
     * Estimated interest or fees amount the payer will have to pay during the lifetime of the loan.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $total_interest
     *
     * @return $this
     */
    public function setTotalInterest($total_interest)
    {
        $this->total_interest = $total_interest;
        return $this;
    }

    /**
     * Estimated interest or fees amount the payer will have to pay during the lifetime of the loan.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getTotalInterest()
    {
        return $this->total_interest;
    }

    /**
     * Status on whether the customer ultimately was approved for and chose to make the payment using the approved installment credit.
     *
     * @param bool $payer_acceptance
     *
     * @return $this
     */
    public function setPayerAcceptance($payer_acceptance)
    {
        $this->payer_acceptance = $payer_acceptance;
        return $this;
    }

    /**
     * Status on whether the customer ultimately was approved for and chose to make the payment using the approved installment credit.
     *
     * @return bool
     */
    public function getPayerAcceptance()
    {
        return $this->payer_acceptance;
    }

    /**
     * Indicates whether the cart amount is editable after payer's acceptance on PayPal side
     *
     * @param bool $cart_amount_immutable
     *
     * @return $this
     */
    public function setCartAmountImmutable($cart_amount_immutable)
    {
        $this->cart_amount_immutable = $cart_amount_immutable;
        return $this;
    }

    /**
     * Indicates whether the cart amount is editable after payer's acceptance on PayPal side
     *
     * @return bool
     */
    public function getCartAmountImmutable()
    {
        return $this->cart_amount_immutable;
    }

}
