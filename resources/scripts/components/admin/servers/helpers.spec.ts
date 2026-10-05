import { describe, expect, it } from 'vitest';
import type { AdminAllocation } from '@/api/admin/nodes/queries';
import { allocationLabel } from './helpers';

const allocation = (alias: string | null): AdminAllocation => ({
    object: 'allocation',
    attributes: {
        id: 1,
        ip: '10.0.0.1',
        alias,
        port: 25565,
        notes: null,
        server_id: null,
        server_name: null,
        assigned: false,
    },
});

describe('allocationLabel', () => {
    it('prefers an alias that differs from the IP', () => {
        expect(allocationLabel(allocation('play.example.com'))).toBe('play.example.com:25565');
    });

    it('falls back to the IP for a missing, empty, or redundant alias', () => {
        expect(allocationLabel(allocation(null))).toBe('10.0.0.1:25565');
        expect(allocationLabel(allocation(''))).toBe('10.0.0.1:25565');
        expect(allocationLabel(allocation('10.0.0.1'))).toBe('10.0.0.1:25565');
    });
});
