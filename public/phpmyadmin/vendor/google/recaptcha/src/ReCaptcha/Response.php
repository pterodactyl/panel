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

namespace ReCaptcha;

/**
 * The response returned from the service.
 */
class Response
{
    /**
     * Success or failure.
     * @var boolean
     */
    private $success = false;

    /**
     * Error code strings.
     * @var array
     */
    private $errorCodes = array();

    /**
     * The hostname of the site where the reCAPTCHA was solved.
     * @var string
     */
    private $hostname;

    /**
     * Timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
     * @var string
     */
    private $challengeTs;

    /**
     * APK package name
     * @var string
     */
    private $apkPackageName;

    /**
     * Score assigned to the request
     * @var float
     */
    private $score;

    /**
     * Action as specified by the page
     * @var string
     */
    private $action;

    /**
     * Build the response from the expected JSON returned by the service.
     *
     * @param string $json
     * @return \ReCaptcha\Response
     */
    public static function fromJson($json)
    {
        $responseData = json_decode($json, true);

        if (!$responseData) {
            return new Response(false, array(ReCaptcha::E_INVALID_JSON));
        }

        $hostname = isset($responseData['hostname']) ? $responseData['hostname'] : null;
        $challengeTs = isset($responseData['challenge_ts']) ? $responseData['challenge_ts'] : null;
        $apkPackageName = isset($responseData['apk_package_name']) ? $responseData['apk_package_name'] : null;
        $score = isset($responseData['score']) ? floatval($responseData['score']) : null;
        $action = isset($responseData['action']) ? $responseData['action'] : null;

        if (isset($responseData['success']) && $responseData['success'] == true) {
            return new Response(true, array(), $hostname, $challengeTs, $apkPackageName, $score, $action);
        }

        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            return new Response(false, $responseData['error-codes'], $hostname, $challengeTs, $apkPackageName, $score, $action);
        }

        return new Response(false, array(ReCaptcha::E_UNKNOWN_ERROR), $hostname, $challengeTs, $apkPackageName, $score, $action);
    }

    /**
     * Constructor.
     *
     * @param boolean $success
     * @param string $hostname
     * @param string $challengeTs
     * @param string $apkPackageName
     * @param float $score
     * @param string $action
     * @param array $errorCodes
     */
    public function __construct($success, array $errorCodes = array(), $hostname = null, $challengeTs = null, $apkPackageName = null, $score = null, $action = null)
    {
        $this->success = $success;
        $this->hostname = $hostname;
        $this->challengeTs = $challengeTs;
        $this->apkPackageName = $apkPackageName;
        $this->score = $score;
        $this->action = $action;
        $this->errorCodes = $errorCodes;
    }

    /**
     * Is success?
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Get error codes.
     *
     * @return array
     */
    public function getErrorCodes()
    {
        return $this->errorCodes;
    }

    /**
     * Get hostname.
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Get challenge timestamp
     *
     * @return string
     */
    public function getChallengeTs()
    {
        return $this->challengeTs;
    }

    /**
     * Get APK package name
     *
     * @return string
     */
    public function getApkPackageName()
    {
        return $this->apkPackageName;
    }
    /**
     * Get score
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    public function toArray()
    {
        return array(
            'success' => $this->isSuccess(),
            'hostname' => $this->getHostname(),
            'challenge_ts' => $this->getChallengeTs(),
            'apk_package_name' => $this->getApkPackageName(),
            'score' => $this->getScore(),
            'action' => $this->getAction(),
            'error-codes' => $this->getErrorCodes(),
        );
    }
}
