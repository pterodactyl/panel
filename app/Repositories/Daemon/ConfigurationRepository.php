<?php
/*
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

namespace Pterodactyl\Repositories\Daemon;

use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class ConfigurationRepository extends BaseRepository implements ConfigurationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(array $overrides = [])
    {
        $node = $this->getNode();
        $structure = [
            'web' => [
                'listen' => $node->daemonListen,
                'ssl' => [
                    'enabled' => (! $node->behind_proxy && $node->scheme === 'https'),
                ],
            ],
            'sftp' => [
                'path' => $node->daemonBase,
                'port' => $node->daemonSFTP,
            ],
            'remote' => [
                'base' => $this->config->get('app.url'),
            ],
            'uploads' => [
                'size_limit' => $node->upload_size,
            ],
            'keys' => [
                $node->daemonSecret,
            ],
        ];

        return $this->getHttpClient()->request('PATCH', '/config', [
            'json' => array_merge($structure, $overrides),
        ]);
    }
}
