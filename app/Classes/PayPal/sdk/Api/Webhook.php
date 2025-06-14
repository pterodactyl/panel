<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;
use Pterodactyl\Classes\PayPal\sdk\Validation\UrlValidator;

/**
 * Class Webhook
 *
 * Represents Webhook resource.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string url
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\WebhookEventType[] event_types
 */
class Webhook extends PayPalResourceModel
{
    /**
     * Identifier of the webhook resource.
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
     * Identifier of the webhook resource.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Webhook notification endpoint url.
     *
     * @param string $url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setUrl($url)
    {
        UrlValidator::validate($url, "Url");
        $this->url = $url;
        return $this;
    }

    /**
     * Webhook notification endpoint url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * List of Webhooks event-types.
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
     * List of Webhooks event-types.
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

    /**
     * Creates the Webhook for the application associated with the access token.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Webhook
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/notifications/webhooks",
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
     * Retrieves the Webhook identified by webhook_id for the application associated with access token.
     *
     * @param string $webhookId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Webhook
     */
    public static function get($webhookId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($webhookId, 'webhookId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/notifications/webhooks/$webhookId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Webhook();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Retrieves all Webhooks for the application associated with access token.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return WebhookList
     */
    public static function getAll($apiContext = null, $restCall = null)
    {
        $payLoad = "";
        $json = self::executeCall(
            "/v1/notifications/webhooks",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new WebhookList();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Updates the Webhook identified by webhook_id for the application associated with access token.
     *
     * @param PatchRequest $patchRequest
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Webhook
     */
    public function update($patchRequest, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($patchRequest, 'patchRequest');
        $payLoad = $patchRequest->toJSON();
        $json = self::executeCall(
            "/v1/notifications/webhooks/{$this->getId()}",
            "PATCH",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * Deletes the Webhook identified by webhook_id for the application associated with access token.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function delete($apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        $payLoad = "";
        self::executeCall(
            "/v1/notifications/webhooks/{$this->getId()}",
            "DELETE",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

}
