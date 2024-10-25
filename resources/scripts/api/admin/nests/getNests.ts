import http, { FractalResponseData, FractalResponseList, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export interface Nest {
    id: number;
    uuid: string;
    author: string;
    name: string;
    description?: string;
    createdAt: Date;
    updatedAt: Date;

    relations: {
        eggs: Egg[] | undefined;
    };
}

export const rawDataToNest = ({ attributes }: FractalResponseData): Nest => ({
    id: attributes.id,
    uuid: attributes.uuid,
    author: attributes.author,
    name: attributes.name,
    description: attributes.description,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    relations: {
        eggs: ((attributes.relationships?.eggs as FractalResponseList | undefined)?.data || []).map(rawDataToEgg),
    },
});

export interface Filters {
    id?: string;
    name?: string;
}

export const Context = createContext<Filters>();

export default (include: string[] = []) => {
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

    return useSWR<PaginatedResult<Nest>>(['nests', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get('/api/application/nests', {
            params: { include: include.join(','), page, ...params },
        });

        return {
            items: (data.data || []).map(rawDataToNest),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
