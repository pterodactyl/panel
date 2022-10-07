<?php

namespace Pterodactyl\Extensions\Filesystem;

use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class S3Filesystem extends AwsS3V3Adapter
{
    private S3ClientInterface $client;

    private string $bucket;

    public function __construct(
        S3ClientInterface $client,
        string $bucket,
        string $prefix = '',
        array $options = [],
    ) {
        parent::__construct(
            $client,
            $bucket,
            $prefix,
            null,
            null,
            $options,
        );

        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function getClient(): S3ClientInterface
    {
        return $this->client;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }
}
