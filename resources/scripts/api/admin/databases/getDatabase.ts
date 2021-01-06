import http from '@/api/http';
import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';

export default (id: number): Promise<Database> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/databases/${id}`)
            .then(({ data }) => resolve(rawDataToDatabase(data)))
            .catch(reject);
    });
};
