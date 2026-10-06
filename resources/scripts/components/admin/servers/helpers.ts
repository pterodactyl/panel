import type { AdminAllocation } from '@/api/admin/nodes/queries';
import type {
    CreateServerFormValues,
    CreateServerValues,
    ServerDetailsValues,
} from '@/components/admin/servers/formValues';
import type {
    CreateServerBody,
    ServerBuildBody,
    CreateServerDatabaseBody,
    ServerDetailsBody,
    ServerStartupBody,
    TransferServerBody,
} from '@/api/admin/servers/queries';

export type { CreateServerFormValues, CreateServerValues, ServerDetailsValues };

export interface ServerBuildValues {
    allocationId: number;
    addAllocations: number[];
    removeAllocations: number[];
    limits: {
        cpu: number;
        threads: string;
        memory: number;
        swap: number;
        disk: number;
        io: number;
    };
    oomDisabled: boolean;
    featureLimits: {
        databases: number;
        allocations: number;
        backups: number;
    };
}

export interface ServerStartupValues {
    startup: string;
    eggId: number;
    image: string;
    skipScripts: boolean;
    environment: Record<string, string>;
}

export interface TransferServerValues {
    nodeId: number;
    allocationId: number;
    allocationAdditional: number[];
}

export interface CreateServerDatabaseValues {
    databaseName: string;
    connectionsFrom: string;
    databaseHostId: number;
    maxConnections: number | null;
}

export const serverDetailsBodyFromFormValues = (values: ServerDetailsValues): ServerDetailsBody => ({
    name: values.name,
    owner_id: values.user,
    external_id: values.externalId === '' ? null : values.externalId,
    description: values.description,
    extensions: values.extensions,
});

export const serverBuildBodyFromFormValues = (values: ServerBuildValues): ServerBuildBody => ({
    allocation_id: values.allocationId,
    add_allocation_ids: values.addAllocations,
    remove_allocation_ids: values.removeAllocations,
    oom_disabled: values.oomDisabled,
    cpu: values.limits.cpu,
    threads: values.limits.threads === '' ? null : values.limits.threads,
    memory: values.limits.memory,
    swap: values.limits.swap,
    disk: values.limits.disk,
    io: values.limits.io,
    database_limit: values.featureLimits.databases,
    allocation_limit: values.featureLimits.allocations,
    backup_limit: values.featureLimits.backups,
});

export const serverStartupBodyFromFormValues = (values: ServerStartupValues): ServerStartupBody => ({
    startup: values.startup,
    egg_id: values.eggId,
    docker_image: values.image,
    skip_scripts: values.skipScripts,
    environment: values.environment,
});

export const transferServerBodyFromFormValues = (values: TransferServerValues): TransferServerBody => ({
    node_id: values.nodeId,
    allocation_id: values.allocationId,
    allocation_additional: values.allocationAdditional,
});

export const createServerBodyFromFormValues = (values: CreateServerValues): CreateServerBody => ({
    name: values.name,
    description: values.description,
    owner_id: values.ownerId,
    start_on_completion: values.startOnCompletion,

    egg_id: values.eggId,
    skip_scripts: values.skipScripts,
    docker_image: values.image,
    startup: values.startup,
    environment: values.environment,

    oom_disabled: !values.enableOomKiller,
    cpu: values.cpu,
    threads: values.threads.length > 0 ? values.threads : null,
    memory: values.memory,
    swap: values.swap,
    disk: values.disk,
    io: values.io,
    database_limit: values.databaseLimit,
    allocation_limit: values.allocationLimit,
    backup_limit: values.backupLimit,
    primary_allocation_id: values.allocationId,
    secondary_allocations_ids: values.allocationAdditional,
    extensions: values.extensions,
});

export const allocationLabel = (allocation: AdminAllocation): string =>
    `${
        allocation.attributes.alias && allocation.attributes.alias !== allocation.attributes.ip
            ? allocation.attributes.alias
            : allocation.attributes.ip
    }:${allocation.attributes.port}`;

export const createServerDatabaseBodyFromFormValues = (
    values: CreateServerDatabaseValues
): CreateServerDatabaseBody => ({
    database: values.databaseName,
    remote: values.connectionsFrom,
    database_host_id: values.databaseHostId,
    max_connections: values.maxConnections,
});
