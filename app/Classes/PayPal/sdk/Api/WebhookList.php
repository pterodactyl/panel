<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class WebhookList
 *
 * List of Webhooks
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Webhook[] webhooks
 */
class WebhookList extends PayPalModel
{
    /**
     * A list of Webhooks
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Webhook[] $webhooks
     *
     * @return $this
     */
    public function setWebhooks($webhooks)
    {
        $this->webhooks = $webhooks;
        return $this;
    }

    /**
     * A list of Webhooks
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Webhook[]
     */
    public function getWebhooks()
    {
        return $this->webhooks;
    }

    /**
     * Append Webhooks to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Webhook $webhook
     * @return $this
     */
    public function addWebhook($webhook)
    {
        if (!$this->getWebhooks()) {
            return $this->setWebhooks(array($webhook));
        } else {
            return $this->setWebhooks(
                array_merge($this->getWebhooks(), array($webhook))
            );
        }
    }

    /**
     * Remove Webhooks from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Webhook $webhook
     * @return $this
     */
    public function removeWebhook($webhook)
    {
        return $this->setWebhooks(
            array_diff($this->getWebhooks(), array($webhook))
        );
    }

}
