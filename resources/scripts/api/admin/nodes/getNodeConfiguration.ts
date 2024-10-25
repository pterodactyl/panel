import http from '@/api/http';

export default (id: number): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}/configuration?format=yaml`)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
