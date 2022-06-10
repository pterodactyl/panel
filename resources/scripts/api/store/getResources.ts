import http from '@/api/http';

export interface Resources {
    balance: number;
    cpu: number;
    disk: number;
    slots: number;
    ports: number;
    backups: number;
    databases: number;
}

export const rawDataToResources = (data: any): Resources => ({
    balance: data.balance,
    cpu: data.cpu,
    disk: data.disk,
    slots: data.slots,
    ports: data.ports,
    backups: data.backups,
    databases: data.databases,
});

export const getResources = async (): Promise<Resources> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/store')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToResources(d.attributes))))
            .catch(reject);
    });
};
