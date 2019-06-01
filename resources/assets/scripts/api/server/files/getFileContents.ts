import http from "@/api/http";
import {AxiosError} from "axios";

export default (server: string, file: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/files/contents`, {
            params: { file },
            responseType: 'text',
            transformResponse: res => res,
        })
            .then(response => resolve(response.data || ''))
            .catch((error: AxiosError) => {
                if (error.response && error.response.data) {
                    error.response.data = JSON.parse(error.response.data);
                }

                reject(error);
            });
    });
}
