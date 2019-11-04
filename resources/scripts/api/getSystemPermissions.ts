import { SubuserPermission } from '@/state/server/subusers';
import http from '@/api/http';

export default (): Promise<SubuserPermission[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/permissions`)
            .then(({ data }) => resolve(data.attributes.permissions))
            .catch(reject);
    });
};
