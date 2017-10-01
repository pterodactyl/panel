<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

class FileRepository extends BaseRepository implements FileRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileStat($path)
    {
        Assert::stringNotEmpty($path, 'First argument passed to getStat must be a non-empty string, received %s.');

        $file = pathinfo($path);
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        $response = $this->getHttpClient()->request('GET', sprintf(
            '/server/' . $this->getAccessServer() . '/file/stat/%s',
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
            '/server/' . $this->getAccessServer() . '/file/f/%s',
            rawurlencode($file['dirname'] . $file['basename'])
        ));

        return object_get(json_decode($response->getBody()), 'content');
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

        return $this->getHttpClient()->request('POST', '/server/' . $this->getAccessServer() . '/file/save', [
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
            '/server/' . $this->getAccessServer() . '/directory/%s',
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
