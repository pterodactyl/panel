import http from '@/api/http';

interface Data {
    renameFrom: string;
    renameTo: string;
}

export default (uuid: string, { renameFrom, renameTo }: Data): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put(`/api/client/servers/${uuid}/files/rename`, {
            // eslint-disable-next-line @typescript-eslint/camelcase
            rename_from: renameFrom,
            // eslint-disable-next-line @typescript-eslint/camelcase
            rename_to: renameTo,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
