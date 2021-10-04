import http from '@/api/http';
import { SSHKey, rawDataToSSHKey } from '@/api/account/ssh/getSSHKeys';

export default (name: string, publicKey: string): Promise<SSHKey> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/ssh', { name, public_key: publicKey })
            .then(({ data }) => resolve(rawDataToSSHKey(data.attributes)))
            .catch(reject);
    });
};
