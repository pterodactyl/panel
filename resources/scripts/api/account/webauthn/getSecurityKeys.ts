import http from '@/api/http';

export interface SecurityKey {
    uuid: string;
    name: string;
    type: 'public-key';
    publicKeyId: string;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToSecurityKey = (data: any): SecurityKey => ({
    uuid: data.uuid,
    name: data.name,
    type: data.type,
    publicKeyId: data.public_key_id,
    createdAt: new Date(data.created_at),
    updatedAt: new Date(data.updated_at),
});

export default async (): Promise<SecurityKey[]> => {
    const { data } = await http.get('/api/client/account/security-keys');

    return (data.data || []).map((datum: any) => rawDataToSecurityKey(datum.attributes));
};
