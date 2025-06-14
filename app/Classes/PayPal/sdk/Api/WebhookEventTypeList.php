<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalModel;

/**
 * Class WebhookEventTypeList
 *
 * List of Webhooks event-types
 *
 * @package PayPal\Api
 *
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\WebhookEventType[] event_types
 */
class WebhookEventTypeList extends PayPalModel
{
    /**
     * A list of Webhooks event-types
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\WebhookEventType[] $event_types
     *
     * @return $this
     */
    public function setEventTypes($event_types)
    {
        $this->event_types = $event_types;
        return $this;
    }

    /**
     * A list of Webhooks event-types
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\WebhookEventType[]
     */
    public function getEventTypes()
    {
        return $this->event_types;
    }

    /**
     * Append EventTypes to the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\WebhookEventType $webhookEventType
     * @return $this
     */
    public function addEventType($webhookEventType)
    {
        if (!$this->getEventTypes()) {
            return $this->setEventTypes(array($webhookEventType));
        } else {
            return $this->setEventTypes(
                array_merge($this->getEventTypes(), array($webhookEventType))
            );
        }
    }

    /**
     * Remove EventTypes from the list.
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\WebhookEventType $webhookEventType
     * @return $this
     */
    public function removeEventType($webhookEventType)
    {
        return $this->setEventTypes(
            array_diff($this->getEventTypes(), array($webhookEventType))
        );
    }

}
