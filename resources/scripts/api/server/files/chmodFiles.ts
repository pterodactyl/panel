import http from '@/api/http';

interface Data {
    file: string;
    mode: string;
}

export default (uuid: string, directory: string, files: Data[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/chmod`, { root: directory, files })
            .then(() => resolve())
            .catch(reject);
    });
};
