<?php

declare(strict_types=1);

namespace Pterodactyl\Data\Extensions;

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
