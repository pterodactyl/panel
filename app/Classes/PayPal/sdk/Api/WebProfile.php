<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;
use Pterodactyl\Classes\PayPal\sdk\Validation\ArgumentValidator;

/**
 * Class WebProfile
 *
 * Payment Web experience profile resource
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string name
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\FlowConfig flow_config
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\InputFields input_fields
 * @property \Pterodactyl\Classes\PayPal\sdk\Api\Presentation presentation
 */
class WebProfile extends PayPalResourceModel
{
    /**
     * ID of the web experience profile.
     *
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
     * ID of the web experience profile.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Name of the web experience profile.
     *
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
     * Name of the web experience profile.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Parameters for flow configuration.
     *
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\FlowConfig $flow_config
     *
     * @return $this
     */
    public function setFlowConfig($flow_config)
    {
        $this->flow_config = $flow_config;
        return $this;
    }

    /**
     * Parameters for flow configuration.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\FlowConfig
     */
    public function getFlowConfig()
    {
        return $this->flow_config;
    }

    /**
     * Parameters for input fields customization.
     *
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\InputFields $input_fields
     *
     * @return $this
     */
    public function setInputFields($input_fields)
    {
        $this->input_fields = $input_fields;
        return $this;
    }

    /**
     * Parameters for input fields customization.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\InputFields
     */
    public function getInputFields()
    {
        return $this->input_fields;
    }

    /**
     * Parameters for style and presentation.
     *
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Api\Presentation $presentation
     *
     * @return $this
     */
    public function setPresentation($presentation)
    {
        $this->presentation = $presentation;
        return $this;
    }

    /**
     * Parameters for style and presentation.
     *
     * @return \Pterodactyl\Classes\PayPal\sdk\Api\Presentation
     */
    public function getPresentation()
    {
        return $this->presentation;
    }

    /**
     * Create a web experience profile by passing the name of the profile and other profile details in the request JSON to the request URI.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return CreateProfileResponse
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/payment-experience/web-profiles/",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new CreateProfileResponse();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Update a web experience profile by passing the ID of the profile to the request URI. In addition, pass the profile details in the request JSON. If your request does not include values for all profile detail fields, the previously set values for the omitted fields are removed by this operation.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function update($apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        $payLoad = $this->toJSON();
        self::executeCall(
            "/v1/payment-experience/web-profiles/{$this->getId()}",
            "PUT",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Partially update an existing web experience profile by passing the ID of the profile to the request URI. In addition, pass a patch object in the request JSON that specifies the operation to perform, path of the profile location to update, and a new value if needed to complete the operation.
     *
     * @param Patch[] $patch
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return bool
     */
    public function partial_update($patch, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($patch, 'patch');
        $payload = array();
        foreach ($patch as $patchObject) {
            $payload[] = $patchObject->toArray();
        }
        $payLoad = json_encode($payload);
        self::executeCall(
            "/v1/payment-experience/web-profiles/{$this->getId()}",
            "PATCH",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Retrieve the details of a particular web experience profile by passing the ID of the profile to the request URI.
     *
     * @param string $profileId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return WebProfile
     */
    public static function get($profileId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($profileId, 'profileId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payment-experience/web-profiles/$profileId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new WebProfile();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Lists all web experience profiles that exist for a merchant (or subject).
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return WebProfile[]
     */
    public static function get_list($apiContext = null, $restCall = null)
    {
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payment-experience/web-profiles/",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return WebProfile::getList($json);
    }

    /**
     * Delete an existing web experience profile by passing the profile ID to the request URI.
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
            "/v1/payment-experience/web-profiles/{$this->getId()}",
            "DELETE",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

}
