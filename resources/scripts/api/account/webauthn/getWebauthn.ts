import http from '@/api/http';

export interface Key {
    id: number;
    name: string;
    createdAt: Date;
    lastUsedAt: Date;
}

export const rawDataToKey = (data: any): Key => ({
    id: data.id,
    name: data.name,
    createdAt: new Date(data.created_at),
    lastUsedAt: new Date(data.last_used_at) || new Date(),
});

export default (): Promise<Key[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/webauthn')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToKey(d.attributes))))
            .catch(reject);
    });
};
