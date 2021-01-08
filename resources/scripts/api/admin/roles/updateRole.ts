import http from '@/api/http';
import { Role, rawDataToRole } from '@/api/admin/roles/getRoles';

export default (id: number, name: string, description?: string): Promise<Role> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/roles/${id}`, {
            name, description,
        })
            .then(({ data }) => resolve(rawDataToRole(data)))
            .catch(reject);
    });
};
