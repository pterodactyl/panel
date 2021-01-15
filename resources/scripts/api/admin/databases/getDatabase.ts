import http from '@/api/http';
import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';

export default (id: number, include: string[] = []): Promise<Database> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/databases/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToDatabase(data)))
            .catch(reject);
    });
};
