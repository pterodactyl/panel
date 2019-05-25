<?php

namespace Pterodactyl\Repositories\Wings;

use stdClass;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

class FileRepository extends BaseWingsRepository implements FileRepositoryInterface
{
    /**
     * Return stat information for a given file.
     *
     * @param string $path
     * @return \stdClass
     *
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getFileStat(string $path): stdClass
    {
        throw new Exception('Function not implemented.');
    }

    /**
     * Return the contents of a given file.
     *
     * @param string   $path
     * @param int|null $notLargerThan the maximum content length in bytes
     * @return string
     *
     * @throws \GuzzleHttp\Exception\TransferException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException
     */
    public function getContent(string $path, int $notLargerThan = null): string
    {
        $response = $this->getHttpClient()->get(
            sprintf('/api/servers/%s/files/contents', $this->getServer()->uuid),
            [
                'query' => ['file' => $path],
            ]
        );

        $length = (int) $response->getHeader('Content-Length')[0] ?? 0;

        if ($notLargerThan && $length > $notLargerThan) {
            throw new FileSizeTooLargeException(
                trans('server.files.exceptions.max_size')
            );
        }

        return $response->getBody()->__toString();
    }

    /**
     * Save new contents to a given file. This works for both creating and updating
     * a file.
     *
     * @param string $path
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function putContent(string $path, string $content): ResponseInterface
    {
        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/write', $this->getServer()->uuid),
            [
                'query' => ['file' => $path],
                'body' => $content,
            ]
        );
    }

    /**
     * Return a directory listing for a given path.
     *
     * @param string $path
     * @return array
     *
     * @throws \GuzzleHttp\Exception\TransferException
     */
    public function getDirectory(string $path): array
    {
        $response = $this->getHttpClient()->get(
            sprintf('/api/servers/%s/files/list-directory', $this->getServer()->uuid),
            [
                'query' => ['directory' => $path],
            ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * Creates a new directory for the server in the given $path.
     *
     * @param string $name
     * @param string $path
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createDirectory(string $name, string $path): ResponseInterface
    {
        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/create-directory', $this->getServer()->uuid),
            [
                'json' => [
                    'name' => $name,
                    'path' => $path,
                ],
            ]
        );
    }

    /**
     * Renames or moves a file on the remote machine.
     *
     * @param string $from
     * @param string $to
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function renameFile(string $from, string $to): ResponseInterface
    {
        return $this->getHttpClient()->put(
            sprintf('/api/servers/%s/files/rename', $this->getServer()->uuid),
            [
                'json' => [
                    'rename_from' => $from,
                    'rename_to' => $to,
                ],
            ]
        );
    }

    /**
     * Copy a given file and give it a unique name.
     *
     * @param string $location
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function copyFile(string $location): ResponseInterface
    {
        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/copy', $this->getServer()->uuid),
            [
                'json' => [
                    'location' => $location,
                ],
            ]
        );
    }

    /**
     * Delete a file or folder for the server.
     *
     * @param string $location
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function deleteFile(string $location): ResponseInterface
    {
        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/delete', $this->getServer()->uuid),
            [
                'json' => [
                    'location' => $location,
                ],
            ]
        );
    }
}
