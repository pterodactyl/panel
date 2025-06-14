<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class WebhookEventType
 *
 * Contains the information for a Webhooks event-type
 *
 * @package PayPal\Api
 *
 * @property string name
 * @property string description
 */
class WebhookEventType extends PayPalResourceModel
{
    /**
     * Unique event-type name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Unique event-type name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Human readable description of the event-type
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Human readable description of the event-type
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Retrieves the list of events-types subscribed by the given Webhook.
     *
     * @param string $webhookId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return WebhookEventTypeList
     */
    public static function subscribedEventTypes($webhookId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($webhookId, 'webhookId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/notifications/webhooks/$webhookId/event-types",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new WebhookEventTypeList();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Retrieves the master list of available Webhooks events-types resources for any webhook to subscribe to.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return WebhookEventTypeList
     */
    public static function availableEventTypes($apiContext = null, $restCall = null)
    {
        $payLoad = "";
        $json = self::executeCall(
            "/v1/notifications/webhooks-event-types",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new WebhookEventTypeList();
        $ret->fromJson($json);
        return $ret;
    }

}
