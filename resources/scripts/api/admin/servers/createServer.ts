import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

interface CreateServerRequest {
    name: string;
    description: string | null;
    user: number;
    egg: number;
    dockerImage: string;
    startup: string;
    skipScripts: boolean;
    oomDisabled: boolean;
    startOnCompletion: boolean;
    environment: string[];

    allocation: {
        default: number;
        additional: number[];
    };

    limits: {
        cpu: number;
        disk: number;
        io: number;
        memory: number;
        swap: number;
        threads: string;
    };

    featureLimits: {
        allocations: number;
        backups: number;
        databases: number;
    };
}

export default (r: CreateServerRequest): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/servers', {
            name: r.name,
            description: r.description,
            user: r.user,
            egg: r.egg,
            docker_image: r.dockerImage,
            startup: r.startup,
            skip_scripts: r.skipScripts,
            oom_disabled: r.oomDisabled,
            start_on_completion: r.startOnCompletion,
            environment: r.environment,

            allocation: {
                default: r.allocation.default,
                additional: r.allocation.additional,
            },

            limits: {
                cpu: r.limits.cpu,
                disk: r.limits.disk,
                io: r.limits.io,
                memory: r.limits.memory,
                swap: r.limits.swap,
                threads: r.limits.threads,
            },

            featureLimits: {
                allocations: r.featureLimits.allocations,
                backups: r.featureLimits.backups,
                databases: r.featureLimits.databases,
            },
        })
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
