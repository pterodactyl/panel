<?php

namespace Pterodactyl\Repositories\Daemon;

use stdClass;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

class FileRepository extends BaseRepository implements FileRepositoryInterface
{
    /**
     * Return stat information for a given file.
     *
     * @param string $path
     * @return \stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFileStat(string $path): stdClass
    {
        $file = str_replace('\\', '/', pathinfo($path));
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        $response = $this->getHttpClient()->request('GET', sprintf(
            'server/file/stat/%s',
            rawurlencode($file['dirname'] . $file['basename'])
        ));

        return json_decode($response->getBody());
    }

    /**
     * Return the contents of a given file if it can be edited in the Panel.
     *
     * @param string $path
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getContent(string $path): string
    {
        $file = str_replace('\\', '/', pathinfo($path));
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        $response = $this->getHttpClient()->request('GET', sprintf(
            'server/file/f/%s',
            rawurlencode($file['dirname'] . $file['basename'])
        ));

        return object_get(json_decode($response->getBody()), 'content');
    }

    /**
     * Save new contents to a given file.
     *
     * @param string $path
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function putContent(string $path, string $content): ResponseInterface
    {
        $file = str_replace('\\', '/', pathinfo($path));
        $file['dirname'] = in_array($file['dirname'], ['.', './', '/']) ? null : trim($file['dirname'], '/') . '/';

        return $this->getHttpClient()->request('POST', 'server/file/save', [
            'json' => [
                'path' => rawurlencode($file['dirname'] . $file['basename']),
                'content' => $content,
            ],
        ]);
    }

    /**
     * Return a directory listing for a given path.
     *
     * @param string $path
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDirectory(string $path): array
    {
        $response = $this->getHttpClient()->request('GET', sprintf('server/directory/%s', rawurlencode($path)));

        return json_decode($response->getBody());
    }

    /**
     * Creates a new directory for the server in the given $path.
     *
     * @param string $name
     * @param string $path
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \RuntimeException
     */
    public function createDirectory(string $name, string $path): ResponseInterface
    {
        throw new RuntimeException('Not implemented.');
    }
}
