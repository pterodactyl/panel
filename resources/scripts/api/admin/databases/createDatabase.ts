import http from '@/api/http';
import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';

export default (name: string): Promise<Database> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/databases', {
            name,
        })
            .then(({ data }) => resolve(rawDataToDatabase(data)))
            .catch(reject);
    });
};
