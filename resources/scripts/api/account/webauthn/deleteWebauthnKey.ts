import http from '@/api/http';

export default (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/account/webauthn/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};
