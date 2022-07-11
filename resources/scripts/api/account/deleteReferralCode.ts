import http from '@/api/http';

export default (id: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/account/referrals/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};
