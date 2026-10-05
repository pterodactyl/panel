import { describe, expect, it } from 'vitest';
import { listQuery, listSorts } from '@/api/queryParameters';
import { adminServersQueryOptions } from '@/api/admin/servers/queries';

describe('list query parameters', () => {
    it('maps sort tokens for known fields onto query-builder sorts', () => {
        const fields = ['name', 'created_at'] as const;

        expect(listSorts(fields, 'name')).toEqual({ name: 'asc' });
        expect(listSorts(fields, '-created_at')).toEqual({ created_at: 'desc' });
        expect(listSorts(fields, undefined)).toBeUndefined();
        expect(listSorts<'name' | 'created_at' | 'memory'>(fields, 'memory')).toBeUndefined();
    });

    it('serializes filters, sorts, page, and page size, omitting empty filters', () => {
        expect(
            listQuery(
                {
                    page: 2,
                    filters: { name: 'alpha', host: '', tags: ['a', 'b'] },
                    sorts: { name: 'desc', id: 1 },
                },
                100
            )
        ).toEqual({
            'filter[name]': 'alpha',
            'filter[tags]': 'a,b',
            sort: '-name,id',
            page: 2,
            per_page: 100,
        });
        expect(listQuery()).toEqual({});
    });

    it('keeps numeric server filters numeric beside the serialized text filters', () => {
        const [key] = adminServersQueryOptions({
            page: 1,
            filters: { '*': 'alpha', node_id: '3', owner_id: [9] },
            sorts: { created_at: 'desc' },
            include: ['user', 'node'],
        }).queryKey;

        expect(key.query).toEqual({
            'filter[*]': 'alpha',
            'filter[node_id]': 3,
            'filter[owner_id]': 9,
            sort: '-created_at',
            page: 1,
            include: 'user,node',
        });
    });
});
