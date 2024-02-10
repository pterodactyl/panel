import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export interface Filters {
    id?: string;
    name?: string;
}

export const Context = createContext<Filters>();

export default (nestId: number, include: string[] = []) => {
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

    return useSWR<PaginatedResult<Egg>>([nestId, 'eggs', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get(`/api/application/nests/${nestId}/eggs`, {
            params: { include: include.join(','), page, ...params },
        });

        return {
            items: (data.data || []).map(rawDataToEgg),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
