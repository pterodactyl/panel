import http from '@/api/http';

export interface SSHKey {
    id: number;
    name: string;
    publicKey: string;
    createdAt: Date;
}

export const rawDataToSSHKey = (data: any): SSHKey => ({
    id: data.id,
    name: data.name,
    publicKey: data.public_key,
    createdAt: new Date(data.created_at),
});

export default (): Promise<SSHKey[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/ssh')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToSSHKey(d.attributes))))
            .catch(reject);
    });
};
