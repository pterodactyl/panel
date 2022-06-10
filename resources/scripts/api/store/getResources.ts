import http, { FractalResponseData } from '@/api/http';

export interface Resources {
    balance: number;
    cpu: number;
    memory: number;
    disk: number;
    slots: number;
    ports: number;
    backups: number;
    databases: number;
}

export const rawDataToResources = ({ attributes: data }: FractalResponseData): Resources => ({
    balance: data.balance,
    cpu: data.cpu,
    memory: data.memory,
    disk: data.disk,
    slots: data.slots,
    ports: data.ports,
    backups: data.backups,
    databases: data.databases,
});

export const getResources = async (): Promise<Resources> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/store')
            .then(({ data }) => resolve(rawDataToResources(data)))
            .catch(reject);
    });
};
