import http from '@/api/http';

export interface AccountLog {
    id: number;
    userId: number;
    action: string;
    ipAddress: string;
    createdAt: Date | null;
}

export const rawDataToAccountLog = (data: any): AccountLog => ({
    id: data.id,
    userId: data.user_id,
    ipAddress: data.ip_address,
    action: data.action,
    createdAt: data.created_at ? new Date(data.created_at) : null,

});

export default (): Promise<AccountLog[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/logs')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToAccountLog(d.attributes))))
            .catch(reject);
    });
};
