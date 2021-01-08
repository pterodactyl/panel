import http from '@/api/http';
import { Mount, rawDataToMount } from '@/api/admin/mounts/getMounts';

export default (id: number, name: string, description: string | null, source: string, target: string, readOnly: boolean, userMountable: boolean): Promise<Mount> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/mounts/${id}`, {
            name, description, source, target, read_only: readOnly, user_mountable: userMountable,
        })
            .then(({ data }) => resolve(rawDataToMount(data)))
            .catch(reject);
    });
};
