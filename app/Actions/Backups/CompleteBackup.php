<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Backups\CompletesBackups;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Backup;
use Throwable;

final readonly class CompleteBackup implements CompletesBackups
{
    public function __construct(private BackupManager $backupManager) {}

    /**
     * The outcome Wings reports for a backup, under the field names of its protocol. A
     * successful backup carries its checksum, the checksum's type and its size in bytes. An
     * S3 backup may list the parts of its multipart upload.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'successful' => ['required', 'boolean'],
            'checksum' => ['nullable', 'string', 'required_if:successful,true'],
            'checksum_type' => ['nullable', 'string', 'required_if:successful,true'],
            'size' => ['nullable', 'integer', 'required_if:successful,true'],
            'parts' => ['nullable', 'array', 'list'],
            'parts.*.etag' => ['required', 'string'],
            'parts.*.part_number' => ['required', 'integer'],
        ];
    }

    /**
     * Records the outcome Wings reported for a backup and, for S3 storage, closes the
     * multipart upload it belongs to.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws DisplayException
     * @throws ValidationException
     * @throws Throwable
     */
    public function complete(Backup $backup, array $input): void
    {
        $validated = Validator::make($input, self::rules())->safe();
        $successful = $validated->boolean('successful');

        // Parts left out or null are listed from S3 instead.
        $parts = $validated->filled('parts')
            ? array_map(fn (int|string $key): array => [
                'etag' => $validated->string('parts.'.$key.'.etag')->toString(),
                'part_number' => $validated->integer('parts.'.$key.'.part_number'),
            ], array_keys($validated->array('parts')))
            : null;

        DB::transaction(function () use ($backup, $validated, $successful, $parts): void {
            $backup->fill([
                'is_successful' => $successful,
                // A failed backup is unlocked so it can be deleted easily.
                'is_locked' => $successful && $backup->is_locked,
                'checksum' => $successful ? $validated->string('checksum_type').':'.$validated->string('checksum') : null,
                'bytes' => $successful ? ($validated['size'] ?? 0) : 0,
                'completed_at' => CarbonImmutable::now(),
            ])->save();

            $adapter = $this->backupManager->adapter();
            if ($adapter instanceof S3Filesystem) {
                $this->completeMultipartUpload($backup, $adapter, $successful, $parts);
            }

            Event::dispatch(new OperationCompleted($backup->server->uuid, 'backup', $successful, $backup->uuid));
        });

        Activity::event($successful ? 'server:backup.complete' : 'server:backup.fail')
            ->subject($backup->server, $backup)
            ->property('name', $backup->name)
            ->log();
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
