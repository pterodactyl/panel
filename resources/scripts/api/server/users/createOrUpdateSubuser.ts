import http from '@/api/http';
import { rawDataToServerSubuser } from '@/api/server/users/getServerSubusers';
import { Subuser } from '@/state/server/subusers';

interface Params {
    email: string;
    permissions: string[];
}

export default (uuid: string, params: Params, subuser?: Subuser): Promise<Subuser> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/users${subuser ? `/${subuser.uuid}` : ''}`, {
            ...params,
        })
            .then((data) => resolve(rawDataToServerSubuser(data.data)))
            .catch(reject);
    });
};
