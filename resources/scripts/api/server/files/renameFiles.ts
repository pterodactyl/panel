import http from '@/api/http';

interface Data {
    to: string;
    from: string;
}

export default (uuid: string, directory: string, files: Data[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put(`/api/client/servers/${uuid}/files/rename`, { root: directory, files })
            .then(() => resolve())
            .catch(reject);
    });
};
