import http from "@/api/http";

export default (server: string, file: string, content: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${server}/files/write`, content, {
            params: { file },
            headers: {
                'Content-Type': 'text/plain; charset=utf-8',
            },
        })
            .then(() => resolve())
            .catch(reject);
    });
}
