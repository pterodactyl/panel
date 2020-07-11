import http from '@/api/http';

interface Data {
    renameFrom: string;
    renameTo: string;
}

export default (uuid: string, directory: string, files: Data[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put(`/api/client/servers/${uuid}/files/rename`, {
            root: directory,
            files: files.map(f => ({ from: f.renameFrom, to: f.renameTo })),
        })
            .then(() => resolve())
            .catch(reject);
    });
};
