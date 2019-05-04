import http from "@/api/http";

export function renameFile(server: string, renameFrom: string, renameTo: string): Promise<void> {
    return new Promise((resolve, reject) => {
        http.put(`/api/client/servers/${server}/files/rename`, {
            rename_from: renameFrom,
            rename_to: renameTo,
        })
            .then(() => resolve())
            .catch(reject);
    });
}
