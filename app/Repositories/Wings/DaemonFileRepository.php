<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;

class DaemonFileRepository extends DaemonRepository
{
    /**
     * Return the contents of a given file.
     *
     * @param string $path
     * @param int|null $notLargerThan the maximum content length in bytes
     * @return string
     *
     * @throws \GuzzleHttp\Exception\TransferException
     * @throws \Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException
     */
    public function getContent(string $path, int $notLargerThan = null): string
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()->get(
            sprintf('/api/servers/%s/files/contents', $this->server->uuid),
            [
                'query' => ['file' => $path],
            ]
        );

        $length = (int) $response->getHeader('Content-Length')[0] ?? 0;

        if ($notLargerThan && $length > $notLargerThan) {
            throw new FileSizeTooLargeException;
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
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/write', $this->server->uuid),
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
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()->get(
            sprintf('/api/servers/%s/files/list-directory', $this->server->uuid),
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
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/create-directory', $this->server->uuid),
            [
                'json' => [
                    'name' => urldecode($name),
                    'path' => urldecode($path),
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
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->put(
            sprintf('/api/servers/%s/files/rename', $this->server->uuid),
            [
                'json' => [
                    'rename_from' => urldecode($from),
                    'rename_to' => urldecode($to),
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
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/copy', $this->server->uuid),
            [
                'json' => [
                    'location' => urldecode($location),
                ],
            ]
        );
    }

    /**
     * Delete a file or folder for the server.
     *
     * @param string|null $root
     * @param array $files
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function deleteFiles(?string $root, array $files): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/delete', $this->server->uuid),
            [
                'json' => [
                    'root' => $root,
                    'files' => $files,
                ],
            ]
        );
    }

    /**
     * Compress the given files or folders in the given root.
     *
     * @param string|null $root
     * @param array $files
     * @return array
     */
    public function compressFiles(?string $root, array $files): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()->post(
            sprintf('/api/servers/%s/files/compress', $this->server->uuid),
            [
                'json' => [
                    'root' => $root ?? '/',
                    'files' => $files,
                ],
            ]
        );

        return json_decode($response->getBody(), true);
    }
}
