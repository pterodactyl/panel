<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class ProcessorResponse
 *
 * Collection of payment response related fields returned from a payment request
 *
 * @package PayPal\Api
 *
 * @property string response_code
 * @property string avs_code
 * @property string cvv_code
 * @property string advice_code
 * @property string eci_submitted
 * @property string vpas
 */
class ProcessorResponse extends PayPalModel
{
    /**
     * Paypal normalized response code, generated from the processor's specific response code
     *
     * @param string $response_code
     *
     * @return $this
     */
    public function setResponseCode($response_code)
    {
        $this->response_code = $response_code;
        return $this;
    }

    /**
     * Paypal normalized response code, generated from the processor's specific response code
     *
     * @return string
     */
    public function getResponseCode()
    {
        return $this->response_code;
    }

    /**
     * Address Verification System response code. https://developer.paypal.com/webapps/developer/docs/classic/api/AVSResponseCodes/
     *
     * @param string $avs_code
     *
     * @return $this
     */
    public function setAvsCode($avs_code)
    {
        $this->avs_code = $avs_code;
        return $this;
    }

    /**
     * Address Verification System response code. https://developer.paypal.com/webapps/developer/docs/classic/api/AVSResponseCodes/
     *
     * @return string
     */
    public function getAvsCode()
    {
        return $this->avs_code;
    }

    /**
     * CVV System response code. https://developer.paypal.com/webapps/developer/docs/classic/api/AVSResponseCodes/
     *
     * @param string $cvv_code
     *
     * @return $this
     */
    public function setCvvCode($cvv_code)
    {
        $this->cvv_code = $cvv_code;
        return $this;
    }

    /**
     * CVV System response code. https://developer.paypal.com/webapps/developer/docs/classic/api/AVSResponseCodes/
     *
     * @return string
     */
    public function getCvvCode()
    {
        return $this->cvv_code;
    }

    /**
     * Provides merchant advice on how to handle declines related to recurring payments
     * Valid Values: ["01_NEW_ACCOUNT_INFORMATION", "02_TRY_AGAIN_LATER", "02_STOP_SPECIFIC_PAYMENT", "03_DO_NOT_TRY_AGAIN", "03_REVOKE_AUTHORIZATION_FOR_FUTURE_PAYMENT", "21_DO_NOT_TRY_AGAIN_CARD_HOLDER_CANCELLED_RECURRRING_CHARGE", "21_CANCEL_ALL_RECURRING_PAYMENTS"]
     *
     * @param string $advice_code
     *
     * @return $this
     */
    public function setAdviceCode($advice_code)
    {
        $this->advice_code = $advice_code;
        return $this;
    }

    /**
     * Provides merchant advice on how to handle declines related to recurring payments
     *
     * @return string
     */
    public function getAdviceCode()
    {
        return $this->advice_code;
    }

    /**
     * Response back from the authorization. Provided by the processor
     *
     * @param string $eci_submitted
     *
     * @return $this
     */
    public function setEciSubmitted($eci_submitted)
    {
        $this->eci_submitted = $eci_submitted;
        return $this;
    }

    /**
     * Response back from the authorization. Provided by the processor
     *
     * @return string
     */
    public function getEciSubmitted()
    {
        return $this->eci_submitted;
    }

    /**
     * Visa Payer Authentication Service status. Will be return from processor
     *
     * @param string $vpas
     *
     * @return $this
     */
    public function setVpas($vpas)
    {
        $this->vpas = $vpas;
        return $this;
    }

    /**
     * Visa Payer Authentication Service status. Will be return from processor
     *
     * @return string
     */
    public function getVpas()
    {
        return $this->vpas;
    }

}
