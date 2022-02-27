import http from '@/api/http';
import { Transformers, Subuser } from '@definitions/user';

interface Params {
    email: string;
    permissions: string[];
}

const deleteSubuser = (uuid: string, userId: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/users/${userId}`)
            .then(() => resolve())
            .catch(reject);
    });
};

const getServerSubusers = (uuid: string): Promise<Subuser[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/users`)
            .then(({ data }) => resolve((data.data || []).map(Transformers.toSubuser)))
            .catch(reject);
    });
};

const createOrUpdateSubuser = (uuid: string, params: Params, subuser?: Subuser): Promise<Subuser> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/users${subuser ? `/${subuser.uuid}` : ''}`, {
            ...params,
        })
            .then(data => resolve(Transformers.toSubuser(data.data)))
            .catch(reject);
    });
};

export {
    deleteSubuser,
    getServerSubusers,
    createOrUpdateSubuser,
};
