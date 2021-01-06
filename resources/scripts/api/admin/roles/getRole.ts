import http from '@/api/http';
import { Role, rawDataToRole } from '@/api/admin/roles/getRoles';

export default (id: number): Promise<Role> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/roles/${id}`)
            .then(({ data }) => resolve(rawDataToRole(data)))
            .catch(reject);
    });
};
