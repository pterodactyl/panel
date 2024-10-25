import { Allocation, rawDataToAllocation } from '@/api/admin/nodes/getAllocations';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';

export interface Filters {
    id?: string;
    ip?: string;
    port?: string;
}

export const Context = createContext<Filters>();

export default (id: number, include: string[] = []) => {
    const { page, filters, sort, sortDirection } = useContext(Context);

    const params = {};
    if (filters !== null) {
        Object.keys(filters).forEach(key => {
            // @ts-expect-error todo
            params['filter[' + key + ']'] = filters[key];
        });
    }

    if (sort !== null) {
        // @ts-expect-error todo
        params.sort = (sortDirection ? '-' : '') + sort;
    }

    return useSWR<PaginatedResult<Allocation>>(['allocations', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get(`/api/application/nodes/${id}/allocations`, {
            params: { include: include.join(','), page, ...params },
        });

        return {
            items: (data.data || []).map(rawDataToAllocation),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
