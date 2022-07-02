import { Subuser } from '@/state/server/subusers';
import http, { FractalResponseData } from '@/api/http';

export const rawDataToServerSubuser = (data: FractalResponseData): Subuser => ({
    uuid: data.attributes.uuid,
    username: data.attributes.username,
    email: data.attributes.email,
    image: data.attributes.image,
    twoFactorEnabled: data.attributes['2fa_enabled'],
    createdAt: new Date(data.attributes.created_at),
    permissions: data.attributes.permissions || [],
    can: (permission) => (data.attributes.permissions || []).indexOf(permission) >= 0,
});

export default (uuid: string): Promise<Subuser[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/users`)
            .then(({ data }) => resolve((data.data || []).map(rawDataToServerSubuser)))
            .catch(reject);
    });
};
