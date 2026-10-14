<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Daemon;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;

final readonly class FilesClient
{
    /** Large archives may take up to fifteen minutes to complete. */
    private const int ARCHIVE_TIMEOUT = 900;

    private function __construct(private string $uuid, private DaemonConnection $connection) {}

    public static function forServer(string $uuid, DaemonConnection $connection): self
    {
        return new self($uuid, $connection);
    }

    public function getContent(string $path, ?int $notLargerThan = null): string
    {
        $response = $this->connection->request('GET', "/api/servers/{$this->uuid}/files/contents", [
            'query' => ['file' => $path],
        ]);
        $length = Str::of($response->header('Content-Length'))->toInteger();
        throw_if($notLargerThan && $length > $notLargerThan, FileSizeTooLargeException::class);

        return $response->body();
    }

    public function putContent(string $path, string $content): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/write", [
            'query' => ['file' => $path],
            'body' => $content,
        ]);
    }

    /** @return list<DaemonFileObject> */
    public function getDirectory(string $path): array
    {
        $response = $this->connection->request('GET', "/api/servers/{$this->uuid}/files/list-directory", [
            'query' => ['directory' => $path],
        ]);
        $data = $this->connection->decode($response, [
            'data' => ['array', 'list'],
            'data.*' => ['array'],
            ...$this->fileRules('data.*'),
        ], expectsList: true);

        /** @var list<DaemonFileObject> $data */
        return $data;
    }

    public function createDirectory(string $name, string $path): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/create-directory", [
            'json' => ['name' => $name, 'path' => $path],
        ]);
    }

    /** @param array<mixed> $files */
    public function renameFiles(?string $root, array $files): Response
    {
        return $this->connection->request('PUT', "/api/servers/{$this->uuid}/files/rename", [
            'json' => ['root' => $root ?? '/', 'files' => $files],
        ]);
    }

    public function copyFile(string $location): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/copy", [
            'json' => ['location' => $location],
        ]);
    }

    /** @param array<mixed> $files */
    public function deleteFiles(?string $root, array $files): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/delete", [
            'json' => ['root' => $root ?? '/', 'files' => $files],
        ]);
    }

    /**
     * @param  array<mixed>  $files
     * @return DaemonFileObject
     */
    public function compressFiles(?string $root, array $files): array
    {
        $response = $this->connection->request('POST', "/api/servers/{$this->uuid}/files/compress", [
            'json' => ['root' => $root ?? '/', 'files' => $files],
            'timeout' => self::ARCHIVE_TIMEOUT,
        ]);
        $data = $this->connection->decode($response, $this->fileRules('data'));

        /** @var DaemonFileObject $data */
        return $data;
    }

    public function decompressFile(?string $root, string $file): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/decompress", [
            'json' => ['root' => $root ?? '/', 'file' => $file],
            'timeout' => self::ARCHIVE_TIMEOUT,
        ]);
    }

    /** @param array<mixed> $files */
    public function chmodFiles(?string $root, array $files): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/chmod", [
            'json' => ['root' => $root ?? '/', 'files' => $files],
        ]);
    }

    /** @param  array{filename?: string|null, use_header?: bool, foreground?: bool, timeout?: int}  $params */
    public function pull(string $url, ?string $directory, array $params = []): Response
    {
        $attributes = [
            'url' => $url,
            'root' => $directory ?? '/',
            'file_name' => $params['filename'] ?? null,
            'use_header' => $params['use_header'] ?? null,
            'foreground' => $params['foreground'] ?? null,
        ];
        $options = ['json' => array_filter($attributes, fn (bool|string|null $value): bool => $value !== null)];

        if (isset($params['timeout'])) {
            $options['timeout'] = $params['timeout'];
        }

        return $this->connection->request('POST', "/api/servers/{$this->uuid}/files/pull", $options);
    }

    /** @return NormalizedValidationRules */
    private function fileRules(string $prefix): array
    {
        return [
            $prefix.'.name' => ['sometimes', 'string'],
            $prefix.'.mode' => ['sometimes', 'string'],
            $prefix.'.mode_bits' => ['sometimes', 'string'],
            $prefix.'.size' => ['sometimes', 'integer:strict'],
            $prefix.'.file' => ['sometimes', 'boolean:strict'],
            $prefix.'.symlink' => ['sometimes', 'boolean:strict'],
            $prefix.'.mime' => ['sometimes', 'string'],
            $prefix.'.created' => ['sometimes', 'string', 'date'],
            $prefix.'.modified' => ['sometimes', 'string', 'date'],
        ];
    }
}
