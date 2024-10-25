import http from '@/api/http';

export interface NodeInformation {
    version: string;
    system: {
        type: string;
        arch: string;
        release: string;
        cpus: number;
    };
}

export default (id: number): Promise<NodeInformation> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}/information`)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
