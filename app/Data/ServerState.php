<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

/**
 * The live power state and resource usage Wings reports for a server.
 */
final readonly class ServerState
{
    public function __construct(
        public string $state,
        public bool $isSuspended,
        public int $memoryBytes,
        public float|int $cpuAbsolute,
        public int $diskBytes,
        public int $networkRxBytes,
        public int $networkTxBytes,
        public int $uptime,
    ) {}

    /**
     * Wings omits whatever it has not measured yet, so every missing value
     * falls back to the idle reading the client API has always reported.
     *
     * @param  DaemonStats  $stats
     */
    public static function fromDaemon(array $stats): self
    {
        $utilization = $stats['utilization'] ?? [];

        return new self(
            $stats['state'] ?? 'stopped',
            $stats['is_suspended'] ?? false,
            $utilization['memory_bytes'] ?? 0,
            $utilization['cpu_absolute'] ?? 0,
            $utilization['disk_bytes'] ?? 0,
            $utilization['network']['rx_bytes'] ?? 0,
            $utilization['network']['tx_bytes'] ?? 0,
            $utilization['uptime'] ?? 0,
        );
    }
}
