<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PrivateLabelCard
 *
 * A resource representing a type of merchant branded payment card. To promote customer value (convenience and earning rewards) and retailer value (merchants drive business using the store cards), PayPal will support as payment method.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string card_number
 * @property string issuer_id
 * @property string issuer_name
 * @property string image_key
 */
class PrivateLabelCard extends PayPalModel
{
    /**
     * encrypted identifier of the private label card instrument.
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
     * encrypted identifier of the private label card instrument.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * last 4 digits of the card number.
     *
     * @param string $card_number
     *
     * @return $this
     */
    public function setCardNumber($card_number)
    {
        $this->card_number = $card_number;
        return $this;
    }

    /**
     * last 4 digits of the card number.
     *
     * @return string
     */
    public function getCardNumber()
    {
        return $this->card_number;
    }

    /**
     * Merchants providing private label store cards have associated issuer account. This value indicates encrypted account number of the associated issuer account.
     *
     * @param string $issuer_id
     *
     * @return $this
     */
    public function setIssuerId($issuer_id)
    {
        $this->issuer_id = $issuer_id;
        return $this;
    }

    /**
     * Merchants providing private label store cards have associated issuer account. This value indicates encrypted account number of the associated issuer account.
     *
     * @return string
     */
    public function getIssuerId()
    {
        return $this->issuer_id;
    }

    /**
     * Merchants providing private label store cards have associated issuer account. This value indicates name on the issuer account.
     *
     * @param string $issuer_name
     *
     * @return $this
     */
    public function setIssuerName($issuer_name)
    {
        $this->issuer_name = $issuer_name;
        return $this;
    }

    /**
     * Merchants providing private label store cards have associated issuer account. This value indicates name on the issuer account.
     *
     * @return string
     */
    public function getIssuerName()
    {
        return $this->issuer_name;
    }

    /**
     * This value indicates URL to access PLCC program logo image
     *
     * @param string $image_key
     *
     * @return $this
     */
    public function setImageKey($image_key)
    {
        $this->image_key = $image_key;
        return $this;
    }

    /**
     * This value indicates URL to access PLCC program logo image
     *
     * @return string
     */
    public function getImageKey()
    {
        return $this->image_key;
    }

}
