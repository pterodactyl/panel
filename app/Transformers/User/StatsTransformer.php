<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Transformers\User;

use GuzzleHttp\Client;
use League\Fractal\TransformerAbstract;
use GuzzleHttp\Exception\ConnectException;

class StatsTransformer extends TransformerAbstract
{
    /**
     * Return a generic transformed subuser array.
     *
     * @return array
     */
    public function transform(Client $client)
    {
        try {
            $res = $client->request('GET', '/server', ['http_errors' => false]);

            if ($res->getStatusCode() !== 200) {
                return [
                    'error' => 'Error: HttpResponseException. Recieved non-200 HTTP status code from daemon: ' . $res->statusCode(),
                ];
            }

            $json = json_decode($res->getBody());

            return [
                'status' => $json->status,
                'resources' => $json->proc,
            ];
        } catch (ConnectException $ex) {
            return [
                'error' => 'Error: ConnectException. Unable to contact the daemon to request server status.',
                'exception' => (config('app.debug')) ? $ex->getMessage() : null,
            ];
        }
    }
}
