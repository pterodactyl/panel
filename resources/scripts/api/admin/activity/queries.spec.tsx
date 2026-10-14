import { describe, expect, it } from 'vitest';
import { adminActivityLogsQueryOptions } from '@/api/admin/activity/queries';

describe('admin activity queries', () => {
    it('serializes log filters with the shared activity query builder', () => {
        const [key] = adminActivityLogsQueryOptions({
            page: 2,
            filters: { event: 'server:power.start', user: '4' },
            sorts: { timestamp: -1 },
        }).queryKey;

        expect(key.query).toEqual({
            include: 'actor',
            page: 2,
            'filter[event_name]': 'server:power.start',
            'filter[user_id]': 4,
            sort: '-timestamp',
        });
    });
});
