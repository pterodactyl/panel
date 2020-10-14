import { rawDataToServerObject, Server } from '@/api/server/getServer';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';

interface QueryParams {
    query?: string;
    page?: number;
    onlyAdmin?: boolean;
}

export default ({ query, page = 1, onlyAdmin = false }: QueryParams): Promise<PaginatedResult<Server>> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client', {
            params: {
                type: onlyAdmin ? 'admin' : undefined,
                'filter[name]': query,
                page,
            },
        })
            .then(({ data }) => resolve({
                items: (data.data || []).map((datum: any) => rawDataToServerObject(datum)),
                pagination: getPaginationSet(data.meta.pagination),
            }))
            .catch(reject);
    });
};
