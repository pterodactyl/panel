<?php

namespace Pterodactyl\Services\Backups;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class InitiateBackupService
{
    /**
     * @var string[]|null
     */
    private $ignoredFiles;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonBackupRepository
     */
    private $daemonBackupRepository;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * InitiateBackupService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     * @param \Pterodactyl\Extensions\Backups\BackupManager $backupManager
     */
    public function __construct(
        BackupRepository $repository,
        ConnectionInterface $connection,
        DaemonBackupRepository $daemonBackupRepository,
        BackupManager $backupManager
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->backupManager = $backupManager;
    }

    /**
     * Sets the files to be ignored by this backup.
     *
     * @param string[]|null $ignored
     * @return $this
     */
    public function setIgnoredFiles(?array $ignored)
    {
        if (is_array($ignored)) {
            foreach ($ignored as $value) {
                Assert::string($value);
            }
        }

        // Set the ignored files to be any values that are not empty in the array. Don't use
        // the PHP empty function here incase anything that is "empty" by default (0, false, etc.)
        // were passed as a file or folder name.
        $this->ignoredFiles = is_null($ignored) ? [] : array_filter($ignored, function ($value) {
            return strlen($value) > 0;
        });

        return $this;
    }

    /**
     * Initiates the backup process for a server on the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param string|null $name
     * @return \Pterodactyl\Models\Backup
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    public function handle(Server $server, string $name = null): Backup
    {
        // Do not allow the user to continue if this server is already at its limit.
        if (! $server->backup_limit || $server->backups()->count() >= $server->backup_limit) {
            throw new TooManyBackupsException($server->backup_limit);
        }

        $previous = $this->repository->getBackupsGeneratedDuringTimespan($server->id, 10);
        if ($previous->count() >= 2) {
            throw new TooManyRequestsHttpException(
                Carbon::now()->diffInSeconds($previous->last()->created_at->addMinutes(10)),
                'Only two backups may be generated within a 10 minute span of time.'
            );
        }

        return $this->connection->transaction(function () use ($server, $name) {
            /** @var \Pterodactyl\Models\Backup $backup */
            $backup = $this->repository->create([
                'server_id' => $server->id,
                'uuid' => Uuid::uuid4()->toString(),
                'name' => trim($name) ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => is_array($this->ignoredFiles) ? array_values($this->ignoredFiles) : [],
                'disk' => $this->backupManager->getDefaultAdapter(),
            ], true, true);

            $url = $this->getS3PresignedUrl(sprintf('%s/%s.tar.gz', $server->uuid, $backup->uuid));

            $this->daemonBackupRepository->setServer($server)
                ->setBackupAdapter($this->backupManager->getDefaultAdapter())
                ->backup($backup, $url);

            return $backup;
        });
    }

    /**
     * Generates a presigned URL for the wings daemon to upload the completed archive
     * to. We use a 30 minute expiration on these URLs to avoid issues with large backups
     * that may take some time to complete.
     *
     * @param string $path
     * @return string|null
     */
    protected function getS3PresignedUrl(string $path)
    {
        $adapter = $this->backupManager->adapter();
        if (! $adapter instanceof AwsS3Adapter) {
            return null;
        }

        $client = $adapter->getClient();

        $request = $client->createPresignedRequest(
            $client->getCommand('PutObject', [
                'Bucket' => $adapter->getBucket(),
                'Key' => $path,
                'ContentType' => 'application/x-gzip',
            ]),
            CarbonImmutable::now()->addMinutes(30)
        );

        return $request->getUri()->__toString();
    }
}
