import http from '@/api/http';

type FormatResponse = 'json' | 'yaml';

export default (id: number, format: FormatResponse = 'yaml'): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}/configuration`, {
            params: {
                format,
            },
        })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
