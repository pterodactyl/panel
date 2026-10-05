<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use PHPUnit\Framework\Assert;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;

class FakeS3Filesystem extends S3Filesystem
{
    public int $getBucketCalls = 0;

    private FakeS3Client $fakeClient;

    public function __construct(string $bucket = 'test-bucket', string $prefix = '', array $options = [])
    {
        $this->fakeClient = new FakeS3Client();

        parent::__construct($this->fakeClient, $bucket, $prefix, $options);
    }

    public function getClient(): FakeS3Client
    {
        return $this->fakeClient;
    }

    public function getBucket(): string
    {
        $this->getBucketCalls++;

        return parent::getBucket();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function deleteObjectCalls(): array
    {
        return $this->fakeClient->deleteObjectCalls;
    }

    public function assertDeleted(string $key): void
    {
        $keys = [];
        foreach ($this->fakeClient->deleteObjectCalls as $call) {
            $candidate = $call['Key'] ?? null;
            if (is_string($candidate)) {
                $keys[] = $candidate;
            }
        }

        Assert::assertContains($key, $keys, sprintf('Expected S3 object [%s] to have been deleted.', $key));
    }
}
