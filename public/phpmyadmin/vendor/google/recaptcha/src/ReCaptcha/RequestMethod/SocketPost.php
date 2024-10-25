<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * BSD 3-Clause License
 * @copyright (c) 2019, Google Inc.
 * @link https://www.google.com/recaptcha
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace ReCaptcha\RequestMethod;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends a POST request to the reCAPTCHA service, but makes use of fsockopen()
 * instead of get_file_contents(). This is to account for people who may be on
 * servers where allow_url_open is disabled.
 */
class SocketPost implements RequestMethod
{
    /**
     * Socket to the reCAPTCHA service
     * @var Socket
     */
    private $socket;

    /**
     * Only needed if you want to override the defaults
     *
     * @param \ReCaptcha\RequestMethod\Socket $socket optional socket, injectable for testing
     * @param string $siteVerifyUrl URL for reCAPTCHA siteverify API
     */
    public function __construct(Socket $socket = null, $siteVerifyUrl = null)
    {
        $this->socket = (is_null($socket)) ? new Socket() : $socket;
        $this->siteVerifyUrl = (is_null($siteVerifyUrl)) ? ReCaptcha::SITE_VERIFY_URL : $siteVerifyUrl;
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        $errno = 0;
        $errstr = '';
        $urlParsed = parse_url($this->siteVerifyUrl);

        if (false === $this->socket->fsockopen('ssl://' . $urlParsed['host'], 443, $errno, $errstr, 30)) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
        }

        $content = $params->toQueryString();

        $request = "POST " . $urlParsed['path'] . " HTTP/1.0\r\n";
        $request .= "Host: " . $urlParsed['host'] . "\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-length: " . strlen($content) . "\r\n";
        $request .= "Connection: close\r\n\r\n";
        $request .= $content . "\r\n\r\n";

        $this->socket->fwrite($request);
        $response = '';

        while (!$this->socket->feof()) {
            $response .= $this->socket->fgets(4096);
        }

        $this->socket->fclose();

        if (0 !== strpos($response, 'HTTP/1.0 200 OK')) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}';
        }

        $parts = preg_split("#\n\s*\n#Uis", $response);

        return $parts[1];
    }
}
