import { rawDataToServerObject, Server } from '@/api/server/getServer';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';

export default (): Promise<PaginatedResult<Server>> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client`, { params: { include: [ 'allocation' ] } })
            .then(({ data }) => resolve({
                items: (data.data || []).map((datum: any) => rawDataToServerObject(datum.attributes)),
                pagination: getPaginationSet(data.meta.pagination),
            }))
            .catch(reject);
    });
};
