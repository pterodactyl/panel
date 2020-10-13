import { PanelPermissions } from '@/state/permissions';
import http from '@/api/http';

export default (): Promise<PanelPermissions> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/permissions')
            .then(({ data }) => resolve(data.attributes.permissions))
            .catch(reject);
    });
};
