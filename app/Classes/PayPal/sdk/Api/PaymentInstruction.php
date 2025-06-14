<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class PaymentInstruction
 *
 * Contain details of how and when the payment should be made to PayPal in cases of manual bank transfer.
 *
 * @package PayPal\Api
 *
 * @property string                                  reference_number
 * @property string                                  instruction_type
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\RecipientBankingInstruction recipient_banking_instruction
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Currency                    amount
 * @property string                                  payment_due_date
 * @property string                                  note
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[]                     links
 */
class PaymentInstruction extends PayPalResourceModel
{
    /**
     * ID of payment instruction
     *
     * @param string $reference_number
     *
     * @return $this
     */
    public function setReferenceNumber($reference_number)
    {
        $this->reference_number = $reference_number;
        return $this;
    }

    /**
     * ID of payment instruction
     *
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->reference_number;
    }

    /**
     * Type of payment instruction
     * Valid Values: ["MANUAL_BANK_TRANSFER", "PAY_UPON_INVOICE"]
     *
     * @param string $instruction_type
     *
     * @return $this
     */
    public function setInstructionType($instruction_type)
    {
        $this->instruction_type = $instruction_type;
        return $this;
    }

    /**
     * Type of payment instruction
     *
     * @return string
     */
    public function getInstructionType()
    {
        return $this->instruction_type;
    }

    /**
     * Recipient bank Details.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\RecipientBankingInstruction $recipient_banking_instruction
     *
     * @return $this
     */
    public function setRecipientBankingInstruction($recipient_banking_instruction)
    {
        $this->recipient_banking_instruction = $recipient_banking_instruction;
        return $this;
    }

    /**
     * Recipient bank Details.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\RecipientBankingInstruction
     */
    public function getRecipientBankingInstruction()
    {
        return $this->recipient_banking_instruction;
    }

    /**
     * Amount to be transferred
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Currency $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Amount to be transferred
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Date by which payment should be received
     *
     * @param string $payment_due_date
     *
     * @return $this
     */
    public function setPaymentDueDate($payment_due_date)
    {
        $this->payment_due_date = $payment_due_date;
        return $this;
    }

    /**
     * Date by which payment should be received
     *
     * @return string
     */
    public function getPaymentDueDate()
    {
        return $this->payment_due_date;
    }

    /**
     * Additional text regarding payment handling
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
     * Additional text regarding payment handling
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Retrieve a payment instruction by passing the payment_id in the request URI. Use this request if you are implementing a solution that includes delayed payment like Pay Upon Invoice (PUI).
     *
     * @param string         $paymentId
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return PaymentInstruction
     */
    public static function get($paymentId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($paymentId, 'paymentId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/payment/$paymentId/payment-instruction",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PaymentInstruction();
        $ret->fromJson($json);
        return $ret;
    }

}
