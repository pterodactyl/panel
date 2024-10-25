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

/**
 * Convenience wrapper around native socket and file functions to allow for
 * mocking.
 */
class Socket
{
    private $handle = null;

    /**
     * fsockopen
     *
     * @see http://php.net/fsockopen
     * @param string $hostname
     * @param int $port
     * @param int $errno
     * @param string $errstr
     * @param float $timeout
     * @return resource
     */
    public function fsockopen($hostname, $port = -1, &$errno = 0, &$errstr = '', $timeout = null)
    {
        $this->handle = fsockopen($hostname, $port, $errno, $errstr, (is_null($timeout) ? ini_get("default_socket_timeout") : $timeout));

        if ($this->handle != false && $errno === 0 && $errstr === '') {
            return $this->handle;
        }
        return false;
    }

    /**
     * fwrite
     *
     * @see http://php.net/fwrite
     * @param string $string
     * @param int $length
     * @return int | bool
     */
    public function fwrite($string, $length = null)
    {
        return fwrite($this->handle, $string, (is_null($length) ? strlen($string) : $length));
    }

    /**
     * fgets
     *
     * @see http://php.net/fgets
     * @param int $length
     * @return string
     */
    public function fgets($length = null)
    {
        return fgets($this->handle, $length);
    }

    /**
     * feof
     *
     * @see http://php.net/feof
     * @return bool
     */
    public function feof()
    {
        return feof($this->handle);
    }

    /**
     * fclose
     *
     * @see http://php.net/fclose
     * @return bool
     */
    public function fclose()
    {
        return fclose($this->handle);
    }
}
