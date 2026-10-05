import { isFiniteNumber, isObject } from '@/lib/objects';

export interface ServerStatsPayload {
    cpu_absolute: number;
    memory_bytes: number;
    disk_bytes: number;
    uptime?: number;
    network: {
        rx_bytes: number;
        tx_bytes: number;
    };
}

const isServerStatsPayload = <T>(value: T): value is T & ServerStatsPayload => {
    if (!isObject(value) || !('network' in value) || !isObject(value.network)) {
        return false;
    }

    return (
        'cpu_absolute' in value &&
        isFiniteNumber(value.cpu_absolute) &&
        'memory_bytes' in value &&
        isFiniteNumber(value.memory_bytes) &&
        'disk_bytes' in value &&
        isFiniteNumber(value.disk_bytes) &&
        (!('uptime' in value) || value.uptime === undefined || isFiniteNumber(value.uptime)) &&
        'rx_bytes' in value.network &&
        isFiniteNumber(value.network.rx_bytes) &&
        'tx_bytes' in value.network &&
        isFiniteNumber(value.network.tx_bytes)
    );
};

export const parseServerStatsPayload = (data: string): ServerStatsPayload | null => {
    try {
        const parsed: unknown = JSON.parse(data);
        return isServerStatsPayload(parsed) ? parsed : null;
    } catch {
        return null;
    }
};
