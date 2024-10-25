import http from '@/api/http';
import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';

export default (
    name: string,
    host: string,
    port: number,
    username: string,
    password: string,
    include: string[] = [],
): Promise<Database> => {
    return new Promise((resolve, reject) => {
        http.post(
            '/api/application/databases',
            {
                name,
                host,
                port,
                username,
                password,
            },
            { params: { include: include.join(',') } },
        )
            .then(({ data }) => resolve(rawDataToDatabase(data)))
            .catch(reject);
    });
};
