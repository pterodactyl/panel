<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class CancelNotification
 *
 * Email/SMS notification.
 *
 * @package PayPal\Api
 *
 * @property string subject
 * @property string note
 * @property bool send_to_merchant
 * @property bool send_to_payer
 */
class CancelNotification extends PayPalModel
{
    /**
     * Subject of the notification.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Subject of the notification.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Note to the payer.
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
     * Note to the payer.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * A flag indicating whether a copy of the email has to be sent to the merchant.
     *
     * @param bool $send_to_merchant
     *
     * @return $this
     */
    public function setSendToMerchant($send_to_merchant)
    {
        $this->send_to_merchant = $send_to_merchant;
        return $this;
    }

    /**
     * A flag indicating whether a copy of the email has to be sent to the merchant.
     *
     * @return bool
     */
    public function getSendToMerchant()
    {
        return $this->send_to_merchant;
    }

    /**
     * A flag indicating whether a copy of the email has to be sent to the payer.
     *
     * @param bool $send_to_payer
     *
     * @return $this
     */
    public function setSendToPayer($send_to_payer)
    {
        $this->send_to_payer = $send_to_payer;
        return $this;
    }

    /**
     * A flag indicating whether a copy of the email has to be sent to the payer.
     *
     * @return bool
     */
    public function getSendToPayer()
    {
        return $this->send_to_payer;
    }

}
