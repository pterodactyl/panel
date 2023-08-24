import http from "@/api/http";

export default (uuid: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/backups`)
            .then(({ data }) => resolve(data.message))
            .catch(reject);
    });
}
