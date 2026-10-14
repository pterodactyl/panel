import type { Server } from '@/api/server/types';
import { isInstallingStatus } from '@/lib/serverStatus';

// The vocabulary every server view shares: what a server is doing, how much it uses, and why it is unavailable.

/** What Wings reports a server doing. */
export type ServerRunState = 'offline' | 'starting' | 'running' | 'stopping';

/** The API's power state, where `stopped` means offline. */
export const runState = (state: ServerRunState | 'stopped'): ServerRunState =>
    state === 'stopped' ? 'offline' : state;

/** A usage value against its limit, where a limit of 0 means unlimited. */
export interface ServerStatMetric {
    readonly value: number;
    readonly limit: number;
    /** Above 80% of the limit is `warning`, above 90% `danger`: the `UiTone` to draw it in. */
    readonly alarm: 'warning' | 'danger' | null;
}

export function statMetric(value: number, limit: number): ServerStatMetric {
    const used = limit ? value / limit : 0;
    let alarm: ServerStatMetric['alarm'] = null;

    if (used > 0.9) {
        alarm = 'danger';
    } else if (used > 0.8) {
        alarm = 'warning';
    }

    return { value, limit, alarm };
}

/** Why a server's pages show a notice instead of their content. */
export type ServerConflict = 'installing' | 'suspended' | 'maintenance' | 'transferring' | 'restoring-backup';

export function serverConflict({ attributes }: Server): ServerConflict | null {
    if (isInstallingStatus(attributes.status)) {
        return 'installing';
    }

    if (attributes.status === 'suspended') {
        return 'suspended';
    }

    if (attributes.is_node_under_maintenance) {
        return 'maintenance';
    }

    if (attributes.is_transferring) {
        return 'transferring';
    }

    return attributes.status === null ? null : 'restoring-backup';
}
