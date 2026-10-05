import { describe, expect, it } from 'vitest';
import { adminNodeListQueryParams } from '@/api/admin/nodes/queries';
import { adminServerListQueryParams } from '@/api/admin/servers/queries';
import {
    parseActivitySearch,
    parseDashboardSearch,
    parseEmailSearch,
    parseNodeListSearch,
    parsePageSearch,
    parseServerListSearch,
    parseUserListSearch,
} from './search';

describe('search value coercion', () => {
    it('reads JSON-parsed values back as the text in the URL', () => {
        expect(parseServerListSearch({ filter: 25565 })).toEqual({ filter: '25565' });
        expect(parseUserListSearch({ filter: true })).toEqual({ filter: 'true' });
        expect(parseActivitySearch({ event: 'server:power', user: 42 })).toEqual({ event: 'server:power', user: '42' });
        expect(parseEmailSearch({ email: 'alex@example.test' })).toEqual({ email: 'alex@example.test' });
    });

    it('drops empty and null values', () => {
        expect(parseServerListSearch({ filter: '   ', page: null })).toEqual({});
        expect(parseActivitySearch({ event: null, user: '' })).toEqual({});
        expect(parseEmailSearch({ email: null })).toEqual({});
    });

    it('accepts only positive integer pages', () => {
        expect(parsePageSearch({ page: 2 })).toEqual({ page: 2 });
        expect(parsePageSearch({ page: '3' })).toEqual({ page: 3 });
        expect(parsePageSearch({ page: 1.5 })).toEqual({});
        expect(parsePageSearch({ page: '2.5' })).toEqual({});
        expect(parsePageSearch({ page: -1 })).toEqual({});
        expect(parsePageSearch({ page: true })).toEqual({});
        expect(parsePageSearch({ page: 'abc' })).toEqual({});
    });

    it('accepts only declared sorts and dashboard types', () => {
        expect(parseUserListSearch({ sort: '-email' })).toEqual({ sort: '-email' });
        expect(parseUserListSearch({ sort: 'password' })).toEqual({});
        expect(parseDashboardSearch({ type: 'admin', page: 4 })).toEqual({ type: 'admin', page: 4 });
        expect(parseDashboardSearch({ type: 'owner' })).toEqual({});
    });
});

describe('parseServerListSearch', () => {
    it('preserves valid server table state', () => {
        expect(parseServerListSearch({ page: '3', filter: '  alpha  ', sort: '-created_at' })).toEqual({
            page: 3,
            filter: 'alpha',
            sort: '-created_at',
        });
    });

    it('discards invalid table state', () => {
        expect(parseServerListSearch({ page: 0, sort: '-uuid' })).toEqual({});
    });

    it('maps URL state to one server query contract for loaders and components', () => {
        expect(adminServerListQueryParams(2, 'owner_id:42', '-name')).toEqual({
            page: 2,
            filters: { owner_id: 42 },
            sorts: { name: 'desc' },
            include: ['user', 'node', 'allocation'],
        });
    });
});

describe('parseNodeListSearch', () => {
    it('preserves valid node table state', () => {
        expect(parseNodeListSearch({ page: '2', filter: '  edge  ', sort: '-memory' })).toEqual({
            page: 2,
            filter: 'edge',
            sort: '-memory',
        });
    });

    it('discards invalid node table state', () => {
        expect(parseNodeListSearch({ page: false, sort: 'location' })).toEqual({});
    });

    it('maps URL state to one node query contract for loaders and components', () => {
        expect(adminNodeListQueryParams(3, 'edge', 'disk')).toEqual({
            page: 3,
            filters: { name: 'edge' },
            sorts: { disk: 'asc' },
        });
    });
});
