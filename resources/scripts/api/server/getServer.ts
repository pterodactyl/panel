import http from '@/api/http';

export interface Allocation {
    ip: string;
    alias: string | null;
    port: number;
    default: boolean;
}

export interface Server {
    id: string;
    uuid: string;
    name: string;
    node: string;
    description: string;
    allocations: Allocation[];
    limits: {
        memory: number;
        swap: number;
        disk: number;
        io: number;
        cpu: number;
    };
    featureLimits: {
        databases: number;
        allocations: number;
    };
}

export const rawDataToServerObject = (data: any): Server => ({
    id: data.identifier,
    uuid: data.uuid,
    name: data.name,
    node: data.node,
    description: data.description ? ((data.description.length > 0) ? data.description : null) : null,
    allocations: [{
        ip: data.allocation.ip,
        alias: null,
        port: data.allocation.port,
        default: true,
    }],
    limits: { ...data.limits },
    featureLimits: { ...data.feature_limits },
});

export default (uuid: string): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}`)
            .then(response => resolve(rawDataToServerObject(response.data.attributes)))
            .catch(reject);
    });
};
