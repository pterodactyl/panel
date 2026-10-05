<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Filesystem;

use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class S3Filesystem extends AwsS3V3Adapter
{
    /**
     * @param  array<string, ApiValue10>  $options
     */
    public function __construct(
        private readonly S3ClientInterface $client,
        private readonly string $bucket,
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
