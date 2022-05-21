import http from '@/api/http';
import { PanelPermissions } from '@/state/permissions';

export default (): Promise<PanelPermissions> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/permissions')
            .then(({ data }) => resolve(data.attributes.permissions))
            .catch(reject);
    });
};
