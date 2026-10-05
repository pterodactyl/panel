<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Contracts\Backups\CompletesBackups;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Models\Backup;
use Throwable;

final readonly class CompleteBackup implements CompletesBackups
{
    public function __construct(private BackupManager $backupManager) {}

    /**
     * Records the outcome Wings reported for a backup and, for S3 storage, closes the
     * multipart upload it belongs to.
     *
     * @param  BackupCompletionData  $data
     *
     * @throws DisplayException
     * @throws Throwable
     */
    public function complete(Backup $backup, array $data): void
    {
        DB::transaction(function () use ($backup, $data): void {
            $successful = $data['successful'];

            $backup->fill([
                'is_successful' => $successful,
                // A failed backup is unlocked so it can be deleted easily.
                'is_locked' => $successful && $backup->is_locked,
                'checksum' => $successful ? ($data['checksum_type'].':'.$data['checksum']) : null,
                'bytes' => $successful ? $data['size'] : 0,
                'completed_at' => CarbonImmutable::now(),
            ])->save();

            $adapter = $this->backupManager->adapter();
            if ($adapter instanceof S3Filesystem) {
                $this->completeMultipartUpload($backup, $adapter, $successful, $data['parts']);
            }

            Event::dispatch(new OperationCompleted($backup->server->uuid, 'backup', $successful, $backup->uuid));
        });
    }

    /**
     * Marks a multipart upload in a given S3-compatible instance as failed or successful for
     * the given backup.
     *
     * @param  list<MultipartUploadPart>|null  $parts
     *
     * @throws DisplayException
     */
    private function completeMultipartUpload(Backup $backup, S3Filesystem $adapter, bool $successful, ?array $parts): void
    {
        // A failed backup may never have started its upload, so there is nothing to abort.
        if (empty($backup->upload_id)) {
            if (! $successful) {
                return;
            }

            throw new DisplayException('Cannot complete backup request: no upload_id present on model.');
        }

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            'UploadId' => $backup->upload_id,
        ];

        $client = $adapter->getClient();
        if (! $successful) {
            $client->execute($client->getCommand('AbortMultipartUpload', $params));

            return;
        }

        $params['MultipartUpload'] = [
            'Parts' => [],
        ];

        if (($parts) === null) {
            $params['MultipartUpload']['Parts'] = $client->execute($client->getCommand('ListParts', $params))['Parts'];
        } else {
            foreach ($parts as $part) {
                $params['MultipartUpload']['Parts'][] = [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number'],
                ];
            }
        }

        $client->execute($client->getCommand('CompleteMultipartUpload', $params));
    }
}
