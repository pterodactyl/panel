import http from '@/api/http';

interface Data {
    renameFrom: string;
    renameTo: string;
}

export default (uuid: string, { renameFrom, renameTo }: Data): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put(`/api/client/servers/${uuid}/files/rename`, {
            rename_from: renameFrom,
            rename_to: renameTo,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
