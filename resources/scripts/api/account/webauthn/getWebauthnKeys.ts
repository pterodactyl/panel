import http from '@/api/http';

export interface WebauthnKey {
    id: number;
    name: string;
    createdAt: Date;
    lastUsedAt: Date | undefined;
}

export const rawDataToWebauthnKey = (data: any): WebauthnKey => ({
    id: data.id,
    name: data.name,
    createdAt: new Date(data.created_at),
    lastUsedAt: data.last_used_at ? new Date(data.last_used_at) : undefined,
});

export default (): Promise<WebauthnKey[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/webauthn')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToWebauthnKey(d.attributes))))
            .catch(reject);
    });
};
