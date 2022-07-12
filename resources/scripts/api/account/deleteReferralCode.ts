import http from '@/api/http';

export default (code: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/account/referrals/${code}`)
            .then(() => resolve())
            .catch(reject);
    });
};
