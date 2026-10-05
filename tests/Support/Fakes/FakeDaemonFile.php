<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Assert;

class FakeDaemonFile extends FakeDaemonHttpClient
{
    public string $content = '';

    /** @var list<array<string, mixed>> */
    public array $directory = [];

    /** @var array<string, mixed> */
    public array $compressed = [];

    public function assertContentFetched(string $path): void
    {
        foreach ($this->callsFor('getContent') as $call) {
            if (($call['path'] ?? null) === $path) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon file content for [%s] to have been fetched.', $path));
    }

    public function assertContentPut(string $path, ?string $content = null): void
    {
        foreach ($this->callsFor('putContent') as $call) {
            if (($call['path'] ?? null) === $path && ($content === null || ($call['content'] ?? null) === $content)) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon file content for [%s] to have been written.', $path));
    }

    public function assertDirectoryListed(string $path): void
    {
        foreach ($this->callsFor('getDirectory') as $call) {
            if (($call['path'] ?? null) === $path) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon directory [%s] to have been listed.', $path));
    }

    public function assertDirectoryCreated(string $name, string $path): void
    {
        foreach ($this->callsFor('createDirectory') as $call) {
            if (($call['name'] ?? null) === $name && ($call['path'] ?? null) === $path) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon directory [%s] in [%s] to have been created.', $name, $path));
    }

    public function assertFilesDeleted(?string $root = null, ?array $files = null): void
    {
        $calls = $this->callsFor('deleteFiles');

        Assert::assertNotEmpty($calls, 'Expected daemon files to have been deleted.');

        if ($root !== null || $files !== null) {
            foreach ($calls as $call) {
                if (($root === null || ($call['root'] ?? null) === $root) && ($files === null || $this->filesMatch($call['files'] ?? null, $files))) {
                    return;
                }
            }

            Assert::fail('Expected daemon files to have been deleted with the given arguments.');
        }
    }

    /**
     * @param  array<int, string>  $files
     */
    public function assertCompressedFiles(?string $root = null, ?array $files = null): void
    {
        $calls = $this->callsFor('compressFiles');

        Assert::assertNotEmpty($calls, 'Expected daemon files to have been compressed.');

        if ($root !== null || $files !== null) {
            foreach ($calls as $call) {
                if (($root === null || ($call['root'] ?? null) === $root) && ($files === null || $this->filesMatch($call['files'] ?? null, $files))) {
                    return;
                }
            }

            Assert::fail('Expected daemon files to have been compressed with the given arguments.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    public function assertFilesRenamed(?string $root = null, ?array $files = null): void
    {
        $calls = $this->callsFor('renameFiles');

        Assert::assertNotEmpty($calls, 'Expected daemon files to have been renamed.');

        if ($root !== null || $files !== null) {
            foreach ($calls as $call) {
                if (($root === null || ($call['root'] ?? null) === $root) && ($files === null || $this->filesMatch($call['files'] ?? null, $files))) {
                    return;
                }
            }

            Assert::fail('Expected daemon files to have been renamed with the given arguments.');
        }
    }

    public function assertFileCopied(?string $location = null): void
    {
        $calls = $this->callsFor('copyFile');

        Assert::assertNotEmpty($calls, 'Expected daemon file to have been copied.');

        if ($location !== null) {
            foreach ($calls as $call) {
                if (($call['location'] ?? null) === $location) {
                    return;
                }
            }

            Assert::fail(sprintf('Expected daemon file [%s] to have been copied.', $location));
        }
    }

    public function assertFileDecompressed(?string $root = null, ?string $file = null): void
    {
        $calls = $this->callsFor('decompressFile');

        Assert::assertNotEmpty($calls, 'Expected daemon file to have been decompressed.');

        if ($root !== null || $file !== null) {
            foreach ($calls as $call) {
                if (($root === null || ($call['root'] ?? null) === $root) && ($file === null || ($call['file'] ?? null) === $file)) {
                    return;
                }
            }

            Assert::fail('Expected daemon file to have been decompressed with the given arguments.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    public function assertFilesChmoded(?string $root = null, ?array $files = null): void
    {
        $calls = $this->callsFor('chmodFiles');

        Assert::assertNotEmpty($calls, 'Expected daemon file permissions to have been updated.');

        if ($root !== null || $files !== null) {
            foreach ($calls as $call) {
                if (($root === null || ($call['root'] ?? null) === $root) && ($files === null || $this->filesMatch($call['files'] ?? null, $files))) {
                    return;
                }
            }

            Assert::fail('Expected daemon file permissions to have been updated with the given arguments.');
        }
    }

    public function assertPulled(string $url): void
    {
        foreach ($this->callsFor('pull') as $call) {
            if (($call['url'] ?? null) === $url) {
                return;
            }
        }

        Assert::fail(sprintf('Expected daemon pull for [%s] to have been triggered.', $url));
    }

    protected function responseFor(Request $request): ?PromiseInterface
    {
        if (! preg_match('#^/api/servers/([^/]+)/files/([^/]+)$#', parse_url($request->url(), PHP_URL_PATH), $matches)) {
            return null;
        }

        $operation = $matches[2];
        $method = match ([$request->method(), $operation]) {
            ['GET', 'contents'] => 'getContent',
            ['GET', 'list-directory'] => 'getDirectory',
            ['POST', 'write'] => 'putContent',
            ['POST', 'create-directory'] => 'createDirectory',
            ['PUT', 'rename'] => 'renameFiles',
            ['POST', 'copy'] => 'copyFile',
            ['POST', 'delete'] => 'deleteFiles',
            ['POST', 'compress'] => 'compressFiles',
            ['POST', 'decompress'] => 'decompressFile',
            ['POST', 'chmod'] => 'chmodFiles',
            ['POST', 'pull'] => 'pull',
            default => null,
        };
        if ($method === null) {
            return null;
        }

        $query = $this->query($request);
        $this->record($method, [
            'path' => $query['file'] ?? $query['directory'] ?? ($request->data()['path'] ?? null),
            'content' => $request->body(),
            'root' => ($request->data()['root'] ?? null),
            'files' => ($request->data()['files'] ?? null),
            'file' => ($request->data()['file'] ?? null),
            'location' => ($request->data()['location'] ?? null),
            'name' => ($request->data()['name'] ?? null),
            'url' => ($request->data()['url'] ?? null),
        ]);

        return $this->reply(match ($method) {
            'getContent' => $this->content,
            'getDirectory' => $this->directory,
            'compressFiles' => $this->compressed,
            default => '',
        });
    }

    /**
     * Compare file payloads ignoring associative key order. Validated request
     * data may reorder keys (e.g. "to" before "from") relative to the test input.
     *
     * @param  array<int|string, mixed>  $expected
     */
    private function filesMatch(mixed $actual, array $expected): bool
    {
        return is_array($actual) && $this->normalizeKeys($actual) === $this->normalizeKeys($expected);
    }

    /**
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private function normalizeKeys(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalizeKeys($item);
            }
        }

        if (array_is_list($value)) {
            return array_values($value);
        }

        ksort($value);

        return $value;
    }
}
