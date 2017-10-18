<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
                'id' => 1,
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
