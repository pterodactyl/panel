import http from '@/api/http';
import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';

export default (
    id: number,
    name: string,
    host: string,
    port: number,
    username: string,
    password: string | undefined,
    include: string[] = [],
): Promise<Database> => {
    return new Promise((resolve, reject) => {
        http.patch(
            `/api/application/databases/${id}`,
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
