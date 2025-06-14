<?php
/**
 * API handler for all REST API calls
 */

namespace Pterodactyl\Classes\PayPal\sdk\Handler;

use Pterodactyl\Classes\PayPal\sdk\Auth\OAuthTokenCredential;
use Pterodactyl\Classes\PayPal\sdk\Common\PayPalUserAgent;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalConstants;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalCredentialManager;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalHttpConfig;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConfigurationException;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalInvalidCredentialException;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalMissingCredentialException;

/**
 * Class RestHandler
 */
class RestHandler implements IPayPalHandler
{
    /**
     * Private Variable
     *
     * @var \Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext $apiContext
     */
    private $apiContext;

    /**
     * Construct
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext $apiContext
     */
    public function __construct($apiContext)
    {
        $this->apiContext = $apiContext;
    }

    /**
     * @param PayPalHttpConfig $httpConfig
     * @param string                    $request
     * @param mixed                     $options
     * @return mixed|void
     * @throws PayPalConfigurationException
     * @throws PayPalInvalidCredentialException
     * @throws PayPalMissingCredentialException
     */
    public function handle($httpConfig, $request, $options)
    {

        $credential = $this->apiContext->getCredential();
        $config = $this->apiContext->getConfig();

        if ($credential == null) {
            // Try picking credentials from the config file
            $credMgr = PayPalCredentialManager::getInstance($config);
            $credValues = $credMgr->getCredentialObject();

            if (!is_array($credValues)) {
                throw new PayPalMissingCredentialException("Empty or invalid credentials passed");
            }

            $credential = new OAuthTokenCredential($credValues['clientId'], $credValues['clientSecret']);
        }

        if ($credential == null || !($credential instanceof OAuthTokenCredential)) {
            throw new PayPalInvalidCredentialException("Invalid credentials passed");
        }

        $httpConfig->setUrl(
            rtrim(trim($this->_getEndpoint($config)), '/') .
            (isset($options['path']) ? $options['path'] : '')
        );

        // Overwrite Expect Header to disable 100 Continue Issue
        $httpConfig->addHeader("Expect", null);

        if (!array_key_exists("User-Agent", $httpConfig->getHeaders())) {
            $httpConfig->addHeader("User-Agent", PayPalUserAgent::getValue(PayPalConstants::SDK_NAME, PayPalConstants::SDK_VERSION));
        }

        if (!is_null($credential) && $credential instanceof OAuthTokenCredential && is_null($httpConfig->getHeader('Authorization'))) {
            $httpConfig->addHeader('Authorization', "Bearer " . $credential->getAccessToken($config), false);
        }

        if ($httpConfig->getMethod() == 'POST' || $httpConfig->getMethod() == 'PUT') {
            $httpConfig->addHeader('PayPal-Request-Id', $this->apiContext->getRequestId());
        }
        // Add any additional Headers that they may have provided
        $headers = $this->apiContext->getRequestHeaders();
        foreach ($headers as $key => $value) {
            $httpConfig->addHeader($key, $value);
        }
    }

    /**
     * End Point
     *
     * @param array $config
     *
     * @return string
     * @throws \Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConfigurationException
     */
    private function _getEndpoint($config)
    {
        if (isset($config['service.EndPoint'])) {
            return $config['service.EndPoint'];
        } else if (isset($config['mode'])) {
            switch (strtoupper($config['mode'])) {
                case 'SANDBOX':
                    return PayPalConstants::REST_SANDBOX_ENDPOINT;
                    break;
                case 'LIVE':
                    return PayPalConstants::REST_LIVE_ENDPOINT;
                    break;
                default:
                    throw new PayPalConfigurationException('The mode config parameter must be set to either sandbox/live');
                    break;
            }
        } else {
            // Defaulting to Sandbox
            return PayPalConstants::REST_SANDBOX_ENDPOINT;
        }
    }
}
