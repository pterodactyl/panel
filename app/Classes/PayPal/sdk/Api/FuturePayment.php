<?php

namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;

/**
 * Class FuturePayment
 *
 * @package PayPal\Api
 */
class FuturePayment extends Payment
{

    /**
     * Extends the Payment object to create future payments
     *
     * @param null $apiContext
     * @param string|null  $clientMetadataId
     * @return $this
     */
    public function create($apiContext = null, $clientMetadataId = null)
    {
        if ($apiContext == null) {
            $apiContext = new ApiContext(self::$credential);
        }
        $headers = array();
        if ($clientMetadataId != null) {
            $headers = array(
                'PAYPAL-CLIENT-METADATA-ID' => $clientMetadataId
            );
        }
        $payLoad = $this->toJSON();
        $call = new PayPalRestCall($apiContext);
        $json = $call->execute(
            array('Pterodactyl\Classes\PayPal\sdk\Handler\RestHandler'),
            "/v1/payments/payment",
            "POST",
            $payLoad,
            $headers
        );
        $this->fromJson($json);

        return $this;

    }

    /**
     * Get a Refresh Token from Authorization Code
     *
     * @param $authorizationCode
     * @param ApiContext $apiContext
     * @return string|null refresh token
     */
    public static function getRefreshToken($authorizationCode, $apiContext = null)
    {
        $apiContext = $apiContext ? $apiContext : new ApiContext(self::$credential);
        $credential = $apiContext->getCredential();
        return $credential->getRefreshToken($apiContext->getConfig(), $authorizationCode);
    }

    /**
     * Updates Access Token using long lived refresh token
     *
     * @param string|null $refreshToken
     * @param ApiContext $apiContext
     * @return void
     */
    public function updateAccessToken($refreshToken, $apiContext)
    {
        $apiContext = $apiContext ? $apiContext : new ApiContext(self::$credential);
        $apiContext->getCredential()->updateAccessToken($apiContext->getConfig(), $refreshToken);
    }
}
