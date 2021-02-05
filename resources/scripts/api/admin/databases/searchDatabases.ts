import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';
import http from '@/api/http';

interface Filters {
    name?: string;
    host?: string;
}

interface Wow {
    [index: string]: string;
}

export default (filters?: Filters): Promise<Database[]> => {
    let params = {};
    if (filters !== undefined) {
        params = Object.keys(filters).map((key) => {
            const a: Wow = {};
            a[`filter[${key}]`] = (filters as unknown as Wow)[key];
            return a;
        });

        console.log(params);
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/databases', { params: { ...params } })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToDatabase)
            ))
            .catch(reject);
    });
};
