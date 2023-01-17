import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export interface Values {
    externalId: string;
    name: string;
    ownerId: number;

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

    allocationId: number;
    addAllocations: number[];
    removeAllocations: number[];
}

export default (id: number, server: Partial<Values>, include: string[] = []): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.patch(
            `/api/application/servers/${id}`,
            {
                external_id: server.externalId,
                name: server.name,
                owner_id: server.ownerId,

                limits: {
                    memory: server.limits?.memory,
                    swap: server.limits?.swap,
                    disk: server.limits?.disk,
                    io: server.limits?.io,
                    cpu: server.limits?.cpu,
                    threads: server.limits?.threads,
                    oom_killer: server.limits?.oomKiller,
                },

                feature_limits: {
                    allocations: server.featureLimits?.allocations,
                    backups: server.featureLimits?.backups,
                    databases: server.featureLimits?.databases,
                },

                allocation_id: server.allocationId,
                add_allocations: server.addAllocations,
                remove_allocations: server.removeAllocations,
            },
            { params: { include: include.join(',') } },
        )
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
