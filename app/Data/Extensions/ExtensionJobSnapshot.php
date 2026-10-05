<?php

declare(strict_types=1);

namespace Pterodactyl\Data\Extensions;

use UnexpectedValueException;

final readonly class ExtensionJobSnapshot
{
    /** @param 'running'|'completed'|'failed' $status */
    public function __construct(
        public string $id,
        public string $extension,
        public string $userUuid,
        public ?string $serverUuid,
        public ?string $permission,
        public string $status,
        public int $percent,
        public string $message,
        public int $sequence,
        public string $updatedAt,
    ) {}

    /** @phpstan-assert self|null $value */
    public static function assertCacheValue(mixed $value): void
    {
        throw_if($value !== null && ! $value instanceof self, UnexpectedValueException::class, 'Invalid cached extension progress snapshot.');
    }

    /** @return array{id: string, extension: string, status: 'running'|'completed'|'failed', percent: int, message: string, sequence: int, updated_at: string} */
    public function payload(): array
    {
        return [
            'id' => $this->id,
            'extension' => $this->extension,
            'status' => $this->status,
            'percent' => $this->percent,
            'message' => $this->message,
            'sequence' => $this->sequence,
            'updated_at' => $this->updatedAt,
        ];
    }
}
