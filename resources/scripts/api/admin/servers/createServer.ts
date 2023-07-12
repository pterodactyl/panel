import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export interface CreateServerRequest {
    externalId: string;
    name: string;
    description: string | null;
    ownerId: number;
    nodeId: number;

    limits: {
        memory: number;
        swap: number;
        disk: number;
        io: number;
        cpu: number;
        threads: string;
        oomKiller: boolean;
    };

    featureLimits: {
        allocations: number;
        backups: number;
        databases: number;
    };

    allocation: {
        default: number;
        additional: number[];
    };

    startup: string;
    environment: Record<string, any>;
    eggId: number;
    image: string;
    skipScripts: boolean;
    startOnCompletion: boolean;
}

export default (r: CreateServerRequest, include: string[] = []): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.post(
            '/api/application/servers',
            {
                externalId: r.externalId,
                name: r.name,
                description: r.description,
                owner_id: r.ownerId,
                node_id: r.nodeId,

                limits: {
                    cpu: r.limits.cpu,
                    disk: r.limits.disk,
                    io: r.limits.io,
                    memory: r.limits.memory,
                    swap: r.limits.swap,
                    threads: r.limits.threads,
                    oom_killer: r.limits.oomKiller,
                },

                feature_limits: {
                    allocations: r.featureLimits.allocations,
                    backups: r.featureLimits.backups,
                    databases: r.featureLimits.databases,
                },

                allocation: {
                    default: r.allocation.default,
                    additional: r.allocation.additional,
                },

                startup: r.startup,
                environment: r.environment,
                egg_id: r.eggId,
                image: r.image,
                skip_scripts: r.skipScripts,
                start_on_completion: r.startOnCompletion,
            },
            { params: { include: include.join(',') } },
        )
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
