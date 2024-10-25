import http from '@/api/http';

export default (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/nests/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};
