import type { Server } from '@/api/server/types';
import type { ServerCardModel } from './dashboard';
import { statMetric } from './server/state';

export interface TestServerOptions {
    identifier?: string;
    uuid?: string;
    name?: string;
    owner?: boolean;
    permissions?: string[];
    status?: Server['attributes']['status'];
    eggFeatures?: string[];
    eggTags?: string[];
}

/** A server payload for fixtures and tests. */
export function createTestServer(options: TestServerOptions = {}): Server {
    const identifier = options.identifier ?? 'test-server';
    const owner = options.owner ?? true;

    return {
        object: 'server',
        attributes: {
            identifier,
            uuid: options.uuid ?? '11111111-1111-4111-8111-111111111111',
            __deprecated_uuid_short: identifier,
            server_identifier: identifier,
            internal_id: 1,
            server_owner: owner,
            name: options.name ?? 'Test server',
            node: 'Test node',
            is_node_under_maintenance: false,
            sftp_details: { ip: '127.0.0.1', port: 2022 },
            description: null,
            limits: { memory: 1024, swap: 0, disk: 1024, io: 500, cpu: 100, threads: null, oom_disabled: true },
            invocation: 'run',
            docker_image: 'test/image',
            egg_features: options.eggFeatures ?? [],
            egg_tags: options.eggTags ?? [],
            feature_limits: { databases: 1, allocations: 1, backups: 1 },
            status: options.status ?? null,
            is_suspended: options.status === 'suspended',
            is_installing: options.status === 'installing',
            is_transferring: false,
            skip_scripts: false,
        },
        meta: { is_server_owner: owner, user_permissions: options.permissions ?? [] },
    };
}

const noop = () => {};

/** A running server's card, with every permission; `options` replace its fields. */
export function createTestServerCard(options: Partial<ServerCardModel> = {}): ServerCardModel {
    return {
        identifier: 'test-server',
        uuid: '11111111-1111-4111-8111-111111111111',
        name: 'Test server',
        description: null,
        address: '127.0.0.1:25565',
        state: {
            kind: 'ready',
            power: 'running',
            cpu: statMetric(12, 100),
            memory: statMetric(256 * 1024 * 1024, 1024 * 1024 * 1024),
            disk: statMetric(512 * 1024 * 1024, 2048 * 1024 * 1024),
        },
        permissions: { start: true, stop: true, restart: true, console: true },
        actions: { start: noop, stop: noop, restart: noop },
        sending: false,
        ...options,
    };
}
