import http from '@/api/http';
import { Role, rawDataToRole } from '@/api/admin/roles/getRoles';

export default (id: number, include: string[] = []): Promise<Role> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/roles/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToRole(data)))
            .catch(reject);
    });
};
