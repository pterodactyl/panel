<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use Composer\CaBundle\CaBundle;

use function base64_encode;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function file_get_contents;
use function function_exists;
use function getenv;
use function ini_get;
use function intval;
use function is_array;
use function is_dir;
use function parse_url;
use function preg_match;
use function stream_context_create;
use function strlen;

use const CURL_IPRESOLVE_V4;
use const CURLINFO_HTTP_CODE;
use const CURLOPT_CAINFO;
use const CURLOPT_CAPATH;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_IPRESOLVE;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_PROXY;
use const CURLOPT_PROXYUSERPWD;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_USERAGENT;
use const PHP_SAPI;

/**
 * Handles HTTP requests
 */
class HttpRequest
{
    /** @var string */
    private $proxyUrl;

    /** @var string */
    private $proxyUser;

    /** @var string */
    private $proxyPass;

    public function __construct()
    {
        global $cfg;

        $this->proxyUrl = $cfg['ProxyUrl'];
        $this->proxyUser = $cfg['ProxyUser'];
        $this->proxyPass = $cfg['ProxyPass'];
    }

    public static function setProxySettingsFromEnv(): void
    {
        global $cfg;

        $httpProxy = getenv('http_proxy');
        $urlInfo = parse_url((string) $httpProxy);
        if (PHP_SAPI !== 'cli' || ! is_array($urlInfo)) {
            return;
        }

        $cfg['ProxyUrl'] = ($urlInfo['host'] ?? '')
            . (isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
        $cfg['ProxyUser'] = $urlInfo['user'] ?? '';
        $cfg['ProxyPass'] = $urlInfo['pass'] ?? '';
    }

    /**
     * Returns information with regards to handling the http request
     *
     * @param array $context Data about the context for which
     *                       to http request is sent
     *
     * @return array of updated context information
     */
    private function handleContext(array $context)
    {
        if (strlen($this->proxyUrl) > 0) {
            $context['http'] = [
                'proxy' => $this->proxyUrl,
                'request_fulluri' => true,
            ];
            if (strlen($this->proxyUser) > 0) {
                $auth = base64_encode($this->proxyUser . ':' . $this->proxyPass);
                $context['http']['header'] = 'Proxy-Authorization: Basic '
                    . $auth . "\r\n";
            }
        }

        return $context;
    }

    /**
     * Creates HTTP request using curl
     *
     * @param mixed $response         HTTP response
     * @param int   $httpStatus       HTTP response status code
     * @param bool  $returnOnlyStatus If set to true, the method would only return response status
     *
     * @return string|bool|null
     */
    private function response(
        $response,
        $httpStatus,
        $returnOnlyStatus
    ) {
        if ($httpStatus == 404) {
            return false;
        }

        if ($httpStatus != 200) {
            return null;
        }

        if ($returnOnlyStatus) {
            return true;
        }

        return $response;
    }

    /**
     * Creates HTTP request using curl
     *
     * @param string $url              Url to send the request
     * @param string $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed  $content          Content to be sent with HTTP request
     * @param string $header           Header to be set for the HTTP request
     *
     * @return string|bool|null
     */
    private function curl(
        $url,
        $method,
        $returnOnlyStatus = false,
        $content = null,
        $header = ''
    ) {
        $curlHandle = curl_init($url);
        if ($curlHandle === false) {
            return null;
        }

        $curlStatus = 1;
        if (strlen($this->proxyUrl) > 0) {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_PROXY, $this->proxyUrl);
            if (strlen($this->proxyUser) > 0) {
                $curlStatus &= (int) curl_setopt(
                    $curlHandle,
                    CURLOPT_PROXYUSERPWD,
                    $this->proxyUser . ':' . $this->proxyPass
                );
            }
        }

        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_USERAGENT, 'phpMyAdmin');

        if ($method !== 'GET') {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($header) {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [$header]);
        }

        if ($method === 'POST') {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $content);
        }

        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);

        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CAINFO, $caPathOrFile);
        }

        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 0);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);

        if (! $curlStatus) {
            return null;
        }

        $response = @curl_exec($curlHandle);
        if ($response === false) {
            return null;
        }

        $httpStatus = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        return $this->response($response, $httpStatus, $returnOnlyStatus);
    }

    /**
     * Creates HTTP request using file_get_contents
     *
     * @param string $url              Url to send the request
     * @param string $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed  $content          Content to be sent with HTTP request
     * @param string $header           Header to be set for the HTTP request
     *
     * @return string|bool|null
     */
    private function fopen(
        $url,
        $method,
        $returnOnlyStatus = false,
        $content = null,
        $header = ''
    ) {
        $context = [
            'http' => [
                'method' => $method,
                'request_fulluri' => true,
                'timeout' => 10,
                'user_agent' => 'phpMyAdmin',
                'header' => 'Accept: */*',
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];
        if ($header) {
            $context['http']['header'] .= "\n" . $header;
        }

        if ($method === 'POST') {
            $context['http']['content'] = $content;
        }

        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            $context['ssl']['capath'] = $caPathOrFile;
        } else {
            $context['ssl']['cafile'] = $caPathOrFile;
        }

        $context = $this->handleContext($context);
        $response = @file_get_contents(
            $url,
            false,
            stream_context_create($context)
        );

        if (! isset($http_response_header)) {
            return null;
        }

        preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $http_response_header[0], $out);
        $httpStatus = intval($out[1]);

        return $this->response($response, $httpStatus, $returnOnlyStatus);
    }

    /**
     * Creates HTTP request
     *
     * @param string $url              Url to send the request
     * @param string $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed  $content          Content to be sent with HTTP request
     * @param string $header           Header to be set for the HTTP request
     *
     * @return string|bool|null
     */
    public function create(
        $url,
        $method,
        $returnOnlyStatus = false,
        $content = null,
        $header = ''
    ) {
        if (function_exists('curl_init')) {
            return $this->curl($url, $method, $returnOnlyStatus, $content, $header);
        }

        if (ini_get('allow_url_fopen')) {
            return $this->fopen($url, $method, $returnOnlyStatus, $content, $header);
        }

        return null;
    }
}
