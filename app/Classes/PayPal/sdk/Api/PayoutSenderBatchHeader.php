<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class PayoutSenderBatchHeader
 *
 * This object represents sender-provided data about a batch header. The data is provided in a POST request. All batch submissions must have a batch header.
 *
 * @package PayPal\Api
 *
 * @property string sender_batch_id
 * @property string email_subject
 * @property string recipient_type
 */
class PayoutSenderBatchHeader extends PayPalModel
{
    /**
     * Sender-created ID for tracking the batch payout in an accounting system. 30 characters max.
     *
     * @param string $sender_batch_id
     *
     * @return $this
     */
    public function setSenderBatchId($sender_batch_id)
    {
        $this->sender_batch_id = $sender_batch_id;
        return $this;
    }

    /**
     * Sender-created ID for tracking the batch payout in an accounting system. 30 characters max.
     *
     * @return string
     */
    public function getSenderBatchId()
    {
        return $this->sender_batch_id;
    }

    /**
     * The subject line text for the email that PayPal sends when a payout item is completed. (The subject line is the same for all recipients.) Maximum of 255 single-byte alphanumeric characters.
     *
     * @param string $email_subject
     *
     * @return $this
     */
    public function setEmailSubject($email_subject)
    {
        $this->email_subject = $email_subject;
        return $this;
    }

    /**
     * The subject line text for the email that PayPal sends when a payout item is completed. (The subject line is the same for all recipients.) Maximum of 255 single-byte alphanumeric characters.
     *
     * @return string
     */
    public function getEmailSubject()
    {
        return $this->email_subject;
    }

    /**
     * The type of ID for a payment receiver. If this field is provided, the payout items without a `recipient_type` will use the provided value. If this field is not provided, each payout item must include a value for the `recipient_type`.
     *
     * @param string $recipient_type
     *
     * @return $this
     */
    public function setRecipientType($recipient_type)
    {
        $this->recipient_type = $recipient_type;
        return $this;
    }

    /**
     * The type of ID for a payment receiver. If this field is provided, the payout items without a `recipient_type` will use the provided value. If this field is not provided, each payout item must include a value for the `recipient_type`.
     *
     * @return string
     */
    public function getRecipientType()
    {
        return $this->recipient_type;
    }

}
