import http from '@/api/http';

export interface ReferralCode {
    code: string;
    createdAt: Date | null;
}

export const rawDataToReferralCode = (data: any): ReferralCode => ({
    code: data.code,
    createdAt: data.created_at ? new Date(data.created_at) : null,
});

export default (): Promise<ReferralCode[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/referrals')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToReferralCode(d.attributes))))
            .catch(reject);
    });
};
