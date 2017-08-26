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

use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Webmozart\Assert\Assert;

class FileRepository extends BaseRepository implements FileRepositoryInterface
{
    public function getFileStat($path)
    {
        Assert::stringNotEmpty($path, 'First argument passed to getStat must be a non-empty string, received %s.');

        $file = pathinfo($path);
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        $response = $this->getHttpClient()->request('GET', sprintf(
            '/server/file/stat/%s',
            rawurlencode($file['dirname'] . $file['basename'])
        ));

        return json_decode($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    public function getContent($path)
    {
        Assert::stringNotEmpty($path, 'First argument passed to getContent must be a non-empty string, received %s.');

        $file = pathinfo($path);
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        $response = $this->getHttpClient()->request('GET', sprintf(
            '/server/file/f/%s',
            rawurlencode($file['dirname'] . $file['basename'])
        ));

        return json_decode($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    public function putContent($path, $content)
    {
        Assert::stringNotEmpty($path, 'First argument passed to putContent must be a non-empty string, received %s.');
        Assert::string($content, 'Second argument passed to putContent must be a string, received %s.');

        $file = pathinfo($path);
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        return $this->getHttpClient()->request('POST', '/server/file/save', [
            'json' => [
                'path' => rawurlencode($file['dirname'] . $file['basename']),
                'content' => $content,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectory($path)
    {
        Assert::string($path, 'First argument passed to getDirectory must be a string, received %s.');

        $response = $this->getHttpClient()->request('GET', sprintf(
            '/server/directory/%s',
            rawurlencode($path)
        ));

        $contents = json_decode($response->getBody());
        $files = [];
        $folders = [];

        foreach ($contents as $value) {
            if ($value->directory) {
                array_push($folders, [
                    'entry' => $value->name,
                    'directory' => trim($path, '/'),
                    'size' => null,
                    'date' => strtotime($value->modified),
                    'mime' => $value->mime,
                ]);
            } elseif ($value->file) {
                array_push($files, [
                    'entry' => $value->name,
                    'directory' => trim($path, '/'),
                    'extension' => pathinfo($value->name, PATHINFO_EXTENSION),
                    'size' => human_readable($value->size),
                    'date' => strtotime($value->modified),
                    'mime' => $value->mime,
                ]);
            }
        }

        return [
            'files' => $files,
            'folders' => $folders,
        ];
    }
}
