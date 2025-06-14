<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalConstants;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class Payment
 *
 * Lets you create, process and manage payments.
 *
 * @package PayPal\Api
 *
 * @property string                              id
 * @property string                              intent
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Payer                   payer
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\PotentialPayerInfo      potential_payer_info
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Payee                   payee
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Transaction[]           transactions
 * @property string[] billing_agreement_tokens
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\PaymentInstruction      payment_instruction
 * @property string                              state
 * @property string                              experience_profile_id
 * @property string                              note_to_payer
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\RedirectUrls            redirect_urls
 * @property string                              failure_reason
 * @property string                              create_time
 * @property string                              update_time
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Links[]                 links
 */
class Payment extends PayPalResourceModel
{
    /**
     * ID of the created payment, the 'transaction ID'
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
     * ID of the created payment, the 'transaction ID'
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Payment intent.
     * Valid Values: ["sale", "authorize", "order"]
     *
     * @param string $intent
     *
     * @return $this
     */
    public function setIntent($intent)
    {
        $this->intent = $intent;
        return $this;
    }

    /**
     * Payment intent.
     *
     * @return string
     */
    public function getIntent()
    {
        return $this->intent;
    }

    /**
     * Source of the funds for this payment represented by a PayPal account or a direct credit card.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Payer $payer
     *
     * @return $this
     */
    public function setPayer($payer)
    {
        $this->payer = $payer;
        return $this;
    }

    /**
     * Source of the funds for this payment represented by a PayPal account or a direct credit card.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Payer
     */
    public function getPayer()
    {
        return $this->payer;
    }

    /**
     * Information that the merchant knows about the payer.  This information is not definitive and only serves as a hint to the UI or any pre-processing logic.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PotentialPayerInfo $potential_payer_info
     *
     * @return $this
     */
    public function setPotentialPayerInfo($potential_payer_info)
    {
        $this->potential_payer_info = $potential_payer_info;
        return $this;
    }

    /**
     * Information that the merchant knows about the payer.  This information is not definitive and only serves as a hint to the UI or any pre-processing logic.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\PotentialPayerInfo
     */
    public function getPotentialPayerInfo()
    {
        return $this->potential_payer_info;
    }

    /**
     * Receiver of funds for this payment. **Readonly for PayPal external REST payments.**
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Payee $payee
     *
     * @return $this
     */
    public function setPayee($payee)
    {
        $this->payee = $payee;
        return $this;
    }

    /**
     * Receiver of funds for this payment. **Readonly for PayPal external REST payments.**
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Payee
     */
    public function getPayee()
    {
        return $this->payee;
    }

    /**
     * ID of the cart to execute the payment.
     *
     * @deprecated Not publicly available
     * @param string $cart
     *
     * @return $this
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * ID of the cart to execute the payment.
     *
     * @deprecated Not publicly available
     * @return string
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Transactional details including the amount and item details.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Transaction[] $transactions
     *
     * @return $this
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
        return $this;
    }

    /**
     * Transactional details including the amount and item details.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Transaction[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Append Transactions to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Transaction $transaction
     * @return $this
     */
    public function addTransaction($transaction)
    {
        if (!$this->getTransactions()) {
            return $this->setTransactions(array($transaction));
        } else {
            return $this->setTransactions(
                array_merge($this->getTransactions(), array($transaction))
            );
        }
    }

    /**
     * Remove Transactions from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Transaction $transaction
     * @return $this
     */
    public function removeTransaction($transaction)
    {
        return $this->setTransactions(
            array_diff($this->getTransactions(), array($transaction))
        );
    }

    /**
     * Applicable for advanced payments like multi seller payment (MSP) to support partial failures
     *
     * @deprecated Not publicly available
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Error[] $failed_transactions
     *
     * @return $this
     */
    public function setFailedTransactions($failed_transactions)
    {
        $this->failed_transactions = $failed_transactions;
        return $this;
    }

    /**
     * Applicable for advanced payments like multi seller payment (MSP) to support partial failures
     *
     * @deprecated Not publicly available
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Error[]
     */
    public function getFailedTransactions()
    {
        return $this->failed_transactions;
    }

    /**
     * Append FailedTransactions to the list.
     *
     * @deprecated Not publicly available
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Error $error
     * @return $this
     */
    public function addFailedTransaction($error)
    {
        if (!$this->getFailedTransactions()) {
            return $this->setFailedTransactions(array($error));
        } else {
            return $this->setFailedTransactions(
                array_merge($this->getFailedTransactions(), array($error))
            );
        }
    }

    /**
     * Remove FailedTransactions from the list.
     *
     * @deprecated Not publicly available
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Error $error
     * @return $this
     */
    public function removeFailedTransaction($error)
    {
        return $this->setFailedTransactions(
            array_diff($this->getFailedTransactions(), array($error))
        );
    }

    /**
     * Collection of PayPal generated billing agreement tokens.
     *
     * @param string[] $billing_agreement_tokens
     *
     * @return $this
     */
    public function setBillingAgreementTokens($billing_agreement_tokens)
    {
        $this->billing_agreement_tokens = $billing_agreement_tokens;
        return $this;
    }

    /**
     * Collection of PayPal generated billing agreement tokens.
     *
     * @return string[]
     */
    public function getBillingAgreementTokens()
    {
        return $this->billing_agreement_tokens;
    }

    /**
     * Append BillingAgreementTokens to the list.
     *
     * @param string $billingAgreementToken
     * @return $this
     */
    public function addBillingAgreementToken($billingAgreementToken)
    {
        if (!$this->getBillingAgreementTokens()) {
            return $this->setBillingAgreementTokens(array($billingAgreementToken));
        } else {
            return $this->setBillingAgreementTokens(
                array_merge($this->getBillingAgreementTokens(), array($billingAgreementToken))
            );
        }
    }

    /**
     * Remove BillingAgreementTokens from the list.
     *
     * @param string $billingAgreementToken
     * @return $this
     */
    public function removeBillingAgreementToken($billingAgreementToken)
    {
        return $this->setBillingAgreementTokens(
            array_diff($this->getBillingAgreementTokens(), array($billingAgreementToken))
        );
    }

    /**
     * Credit financing offered to payer on PayPal side. Returned in payment after payer opts-in
     *
     * @deprecated Not publicly available
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\CreditFinancingOffered $credit_financing_offered
     *
     * @return $this
     */
    public function setCreditFinancingOffered($credit_financing_offered)
    {
        $this->credit_financing_offered = $credit_financing_offered;
        return $this;
    }

    /**
     * Credit financing offered to payer on PayPal side. Returned in payment after payer opts-in
     *
     * @deprecated Not publicly available
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\CreditFinancingOffered
     */
    public function getCreditFinancingOffered()
    {
        return $this->credit_financing_offered;
    }

    /**
     * Instructions for the payer to complete this payment.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\PaymentInstruction $payment_instruction
     *
     * @return $this
     */
    public function setPaymentInstruction($payment_instruction)
    {
        $this->payment_instruction = $payment_instruction;
        return $this;
    }

    /**
     * Instructions for the payer to complete this payment.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\PaymentInstruction
     */
    public function getPaymentInstruction()
    {
        return $this->payment_instruction;
    }

    /**
     * Payment state.
     * Valid Values: ["created", "approved", "failed", "partially_completed", "in_progress"]
     *
     * @param string $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Payment state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * PayPal generated identifier for the merchant's payment experience profile. Refer to [this](https://developer.paypal.com/webapps/developer/docs/api/#payment-experience) link to create experience profile ID.
     *
     * @param string $experience_profile_id
     *
     * @return $this
     */
    public function setExperienceProfileId($experience_profile_id)
    {
        $this->experience_profile_id = $experience_profile_id;
        return $this;
    }

    /**
     * PayPal generated identifier for the merchant's payment experience profile. Refer to [this](https://developer.paypal.com/webapps/developer/docs/api/#payment-experience) link to create experience profile ID.
     *
     * @return string
     */
    public function getExperienceProfileId()
    {
        return $this->experience_profile_id;
    }

    /**
     * free-form field for the use of clients to pass in a message to the payer
     *
     * @param string $note_to_payer
     *
     * @return $this
     */
    public function setNoteToPayer($note_to_payer)
    {
        $this->note_to_payer = $note_to_payer;
        return $this;
    }

    /**
     * free-form field for the use of clients to pass in a message to the payer
     *
     * @return string
     */
    public function getNoteToPayer()
    {
        return $this->note_to_payer;
    }

    /**
     * Set of redirect URLs you provide only for PayPal-based payments.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\RedirectUrls $redirect_urls
     *
     * @return $this
     */
    public function setRedirectUrls($redirect_urls)
    {
        $this->redirect_urls = $redirect_urls;
        return $this;
    }

    /**
     * Set of redirect URLs you provide only for PayPal-based payments.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\RedirectUrls
     */
    public function getRedirectUrls()
    {
        return $this->redirect_urls;
    }

    /**
     * Failure reason code returned when the payment failed for some valid reasons.
     * Valid Values: ["UNABLE_TO_COMPLETE_TRANSACTION", "INVALID_PAYMENT_METHOD", "PAYER_CANNOT_PAY", "CANNOT_PAY_THIS_PAYEE", "REDIRECT_REQUIRED", "PAYEE_FILTER_RESTRICTIONS"]
     *
     * @param string $failure_reason
     *
     * @return $this
     */
    public function setFailureReason($failure_reason)
    {
        $this->failure_reason = $failure_reason;
        return $this;
    }

    /**
     * Failure reason code returned when the payment failed for some valid reasons.
     *
     * @return string
     */
    public function getFailureReason()
    {
        return $this->failure_reason;
    }

    /**
     * Payment creation time as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $create_time
     *
     * @return $this
     */
    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;
        return $this;
    }

    /**
     * Payment creation time as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Payment update time as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @param string $update_time
     *
     * @return $this
     */
    public function setUpdateTime($update_time)
    {
        $this->update_time = $update_time;
        return $this;
    }

    /**
     * Payment update time as defined in [RFC 3339 Section 5.6](http://tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Get Approval Link
     *
     * @return null|string
     */
    public function getApprovalLink()
    {
        return $this->getLink(PayPalConstants::APPROVAL_URL);
    }

    /**
     * Create and process a payment by passing a payment object that includes the intent, payer, and transactions in the body of the request JSON. For PayPal payments, include redirect URLs in the payment object.
     *
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return Payment
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/payments/payment",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * Look up a particular payment resource by passing the payment_id in the request URI.
     *
     * @param string         $paymentId
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return Payment
     */
    public static function get($paymentId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($paymentId, 'paymentId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/payment/$paymentId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Payment();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Use this call to partially update the payment resource for the given identifier. Allowed objects are amount, shipping_address, invoice_id and custom. Please note that it is not possible to use patch after execute has been called.
     *
     * @param PatchRequest   $patchRequest
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return boolean
     */
    public function update($patchRequest, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($patchRequest, 'patchRequest');
        $payLoad = $patchRequest->toJSON();
        self::executeCall(
            "/v1/payments/payment/{$this->getId()}",
            "PATCH",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Execute (complete) a PayPal payment that has been approved by the payer. Optionally update selective payment information when executing the payment.
     *
     * @param PaymentExecution $paymentExecution
     * @param ApiContext       $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall   $restCall   is the Rest Call Service that is used to make rest calls
     * @return Payment
     */
    public function execute($paymentExecution, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($paymentExecution, 'paymentExecution');
        $payLoad = $paymentExecution->toJSON();
        $json = self::executeCall(
            "/v1/payments/payment/{$this->getId()}/execute",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * List payments in any state (created, approved, failed, etc.). Payments returned are the payments made to the merchant issuing the request.
     *
     * @param array          $params
     * @param ApiContext     $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall   is the Rest Call Service that is used to make rest calls
     * @return PaymentHistory
     */
    public static function all($params, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($params, 'params');
        $payLoad = "";
        $allowedParams = array(
            'count' => 1,
            'start_id' => 1,
            'start_index' => 1,
            'start_time' => 1,
            'end_time' => 1,
            'payee_id' => 1,
            'sort_by' => 1,
            'sort_order' => 1,
        );
        $json = self::executeCall(
            "/v1/payments/payment?" . http_build_query(array_intersect_key($params, $allowedParams)),
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PaymentHistory();
        $ret->fromJson($json);
        return $ret;
    }

}
