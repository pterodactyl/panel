import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { Transformers, Server } from '@definitions/user';

interface QueryParams {
    query?: string;
    page?: number;
    type?: string;
}

export default ({ query, ...params }: QueryParams): Promise<PaginatedResult<Server>> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client', {
            params: {
                'filter[*]': query,
                ...params,
            },
        })
            .then(({ data }) => resolve({
                items: (data.data || []).map(Transformers.toServer),
                pagination: getPaginationSet(data.meta.pagination),
            }))
            .catch(reject);
    });
};
