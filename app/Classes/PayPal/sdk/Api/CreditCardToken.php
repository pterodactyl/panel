<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CreditCardToken
 *
 * A resource representing a credit card that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string credit_card_id
 * @property string payer_id
 * @property string last4
 * @property string type
 * @property int    expire_month
 * @property int    expire_year
 */
class CreditCardToken extends PayPalModel
{
    /**
     * ID of credit card previously stored using `/vault/credit-card`.
     *
     * @param string $credit_card_id
     *
     * @return $this
     */
    public function setCreditCardId($credit_card_id)
    {
        $this->credit_card_id = $credit_card_id;
        return $this;
    }

    /**
     * ID of credit card previously stored using `/vault/credit-card`.
     *
     * @return string
     */
    public function getCreditCardId()
    {
        return $this->credit_card_id;
    }

    /**
     * A unique identifier that you can assign and track when storing a credit card or using a stored credit card. This ID can help to avoid unintentional use or misuse of credit cards. This ID can be any value you would like to associate with the saved card, such as a UUID, username, or email address.  **Required when using a stored credit card if a payer_id was originally provided when storing the credit card in vault.**
     *
     * @param string $payer_id
     *
     * @return $this
     */
    public function setPayerId($payer_id)
    {
        $this->payer_id = $payer_id;
        return $this;
    }

    /**
     * A unique identifier that you can assign and track when storing a credit card or using a stored credit card. This ID can help to avoid unintentional use or misuse of credit cards. This ID can be any value you would like to associate with the saved card, such as a UUID, username, or email address.  **Required when using a stored credit card if a payer_id was originally provided when storing the credit card in vault.**
     *
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * Last four digits of the stored credit card number.
     *
     * @param string $last4
     *
     * @return $this
     */
    public function setLast4($last4)
    {
        $this->last4 = $last4;
        return $this;
    }

    /**
     * Last four digits of the stored credit card number.
     *
     * @return string
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * Credit card type. Valid types are: `visa`, `mastercard`, `discover`, `amex`. Values are presented in lowercase and not should not be used for display.
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
     * Credit card type. Valid types are: `visa`, `mastercard`, `discover`, `amex`. Values are presented in lowercase and not should not be used for display.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Expiration month with no leading zero. Acceptable values are 1 through 12.
     *
     * @param int $expire_month
     *
     * @return $this
     */
    public function setExpireMonth($expire_month)
    {
        $this->expire_month = $expire_month;
        return $this;
    }

    /**
     * Expiration month with no leading zero. Acceptable values are 1 through 12.
     *
     * @return int
     */
    public function getExpireMonth()
    {
        return $this->expire_month;
    }

    /**
     * 4-digit expiration year.
     *
     * @param int $expire_year
     *
     * @return $this
     */
    public function setExpireYear($expire_year)
    {
        $this->expire_year = $expire_year;
        return $this;
    }

    /**
     * 4-digit expiration year.
     *
     * @return int
     */
    public function getExpireYear()
    {
        return $this->expire_year;
    }

}
