<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class FakeS3Client extends S3Client
{
    /** @var list<array<string, mixed>> */
    public array $deleteObjectCalls = [];

    public function __construct()
    {
        // AwsClient derives the service name from the concrete class name, so
        // provide the real S3 identifiers explicitly instead of "fakes3".
        parent::__construct([
            'service' => 's3',
            'exception_class' => S3Exception::class,
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => ['key' => 'fake', 'secret' => 'fake'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function deleteObject(array $args = []): Result
    {
        $this->deleteObjectCalls[] = $args;

        return new Result([]);
    }
}
