<?php

namespace Pterodactyl\Classes\PayPal\sdk\Core;

use Pterodactyl\Classes\PayPal\sdk\Auth\OAuthTokenCredential;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalInvalidCredentialException;

/**
 * Class PayPalCredentialManager
 *
 * PayPalCredentialManager holds all the credential information in one place.
 *
 * @package PayPal\Core
 */
class PayPalCredentialManager
{
    /**
     * Singleton Object
     *
     * @var PayPalCredentialManager
     */
    private static $instance;

    /**
     * Hashmap to contain credentials for accounts.
     *
     * @var array
     */
    private $credentialHashmap = array();

    /**
     * Contains the API username of the default account to use
     * when authenticating API calls
     *
     * @var string
     */
    private $defaultAccountName;

    /**
     * Constructor initialize credential for multiple accounts specified in property file
     *
     * @param $config
     * @throws \Exception
     */
    private function __construct($config)
    {
        try {
            $this->initCredential($config);
        } catch (\Exception $e) {
            $this->credentialHashmap = array();
            throw $e;
        }
    }

    /**
     * Create singleton instance for this class.
     *
     * @param array|null $config
     * @return PayPalCredentialManager
     */
    public static function getInstance($config = null)
    {
        if (!self::$instance) {
            self::$instance = new self($config == null ? PayPalConfigManager::getInstance()->getConfigHashmap() : $config);
        }
        return self::$instance;
    }

    /**
     * Load credentials for multiple accounts, with priority given to Signature credential.
     *
     * @param array $config
     */
    private function initCredential($config)
    {
        $suffix = 1;
        $prefix = "acct";

        $arr = array();
        foreach ($config as $k => $v) {
            if (strstr($k, $prefix)) {
                $arr[$k] = $v;
            }
        }
        $credArr = $arr;

        $arr = array();
        foreach ($config as $key => $value) {
            $pos = strpos($key, '.');
            if (strstr($key, "acct")) {
                $arr[] = substr($key, 0, $pos);
            }
        }
        $arrayPartKeys = array_unique($arr);

        $key = $prefix . $suffix;
        $userName = null;
        while (in_array($key, $arrayPartKeys)) {
            if (isset($credArr[$key . ".ClientId"]) && isset($credArr[$key . ".ClientId"])) {
                $userName = $key;
                $this->credentialHashmap[$userName] = new OAuthTokenCredential(
                    $credArr[$key . ".ClientId"],
                    $credArr[$key . ".ClientSecret"]
                );
            }
            if ($userName && $this->defaultAccountName == null) {
                if (array_key_exists($key . '.UserName', $credArr)) {
                    $this->defaultAccountName = $credArr[$key . '.UserName'];
                } else {
                    $this->defaultAccountName = $key;
                }
            }
            $suffix++;
            $key = $prefix . $suffix;
        }

    }

    /**
     * Sets credential object for users
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Auth\OAuthTokenCredential $credential
     * @param string|null   $userId  User Id associated with the account
     * @param bool $default If set, it would make it as a default credential for all requests
     *
     * @return $this
     */
    public function setCredentialObject(OAuthTokenCredential $credential, $userId = null, $default = true)
    {
        $key = $userId == null ? 'default' : $userId;
        $this->credentialHashmap[$key] = $credential;
        if ($default) {
            $this->defaultAccountName = $key;
        }
        return $this;
    }

    /**
     * Obtain Credential Object based on UserId provided.
     *
     * @param null $userId
     * @return OAuthTokenCredential
     * @throws PayPalInvalidCredentialException
     */
    public function getCredentialObject($userId = null)
    {
        if ($userId == null && array_key_exists($this->defaultAccountName, $this->credentialHashmap)) {
            $credObj = $this->credentialHashmap[$this->defaultAccountName];
        } else if (array_key_exists($userId, $this->credentialHashmap)) {
            $credObj = $this->credentialHashmap[$userId];
        }

        if (empty($credObj)) {
            throw new PayPalInvalidCredentialException("Credential not found for " .  ($userId ? $userId : " default user") .
            ". Please make sure your configuration/APIContext has credential information");
        }
        return $credObj;
    }

    /**
     * Disabling __clone call
     */
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

}
