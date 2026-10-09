import { AxiosError, AxiosHeaders } from 'axios';
import { afterEach, expect, it } from 'vitest';
import http from '@/api/http';
import { queryClient } from '@/api/queryClient';
import { serverFilesQueryOptions as panelFiles } from '@/api/server/files/queries';
import { serverBackupsQueryOptions as panelBackups } from '@/api/server/backups/queries';
import { serverResourceUsageQueryOptions as panelResources } from '@/api/account/servers/queries';
import { serverQueryOptions as panelServer, updateServerQueryData, type Server } from '@/api/server/queries';
import {
    serverFilesQueryOptions,
    serverFileContentQueryOptions,
    serverBackupsQueryOptions,
    serverResourcesQueryOptions,
    serverLogsQueryOptions,
    serverQueryOptions,
    serverStartupQueryOptions,
    invalidateServerData,
} from './server';

const originalAdapter = http.defaults.adapter;

afterEach(() => {
    queryClient.clear();
    http.defaults.adapter = originalAdapter;
});

it('shares native cache identities and typed data with core consumers', () => {
    const files = { object: 'list' as const, data: [] };

    queryClient.setQueryData(panelFiles('alpha', '/nested/').queryKey, files);

    expect(queryClient.getQueryData(serverFilesQueryOptions('alpha', '/nested//').queryKey)).toEqual(files);
    expect(serverBackupsQueryOptions('alpha', 2).queryKey).toEqual(panelBackups('alpha', 2).queryKey);
});

it('invalidates all selected server pages and directories while preserving other servers and domains', async () => {
    const targets = [
        serverFilesQueryOptions('alpha', '/').queryKey,
        serverFilesQueryOptions('alpha', '/nested').queryKey,
        serverFileContentQueryOptions('alpha', '/one.txt').queryKey,
        serverFileContentQueryOptions('alpha', '/two.txt').queryKey,
        serverBackupsQueryOptions('alpha', 1).queryKey,
        serverBackupsQueryOptions('alpha', 2).queryKey,
    ];
    const unrelated = [serverFilesQueryOptions('beta', '/').queryKey, serverBackupsQueryOptions('beta', 1).queryKey];

    for (const key of [...targets, ...unrelated]) {
        queryClient.setQueryData(key, 'cached');
    }

    await invalidateServerData('alpha', ['files', 'files', 'fileContent', 'backups']);

    for (const key of targets) {
        expect(queryClient.getQueryState(key)?.isInvalidated).toBe(true);
    }

    for (const key of unrelated) {
        expect(queryClient.getQueryState(key)?.isInvalidated).toBe(false);
    }
});

it('fetches through the shared transport and serves the result to a core consumer without another request', async () => {
    let requests = 0;

    http.defaults.adapter = async (config) => {
        requests++;
        const url = new URL(config.url!, 'https://panel.test');

        expect(url.pathname).toBe('/api/client/servers/alpha/files/list');
        expect(url.searchParams.get('directory')).toBe('/nested');
        expect(config.signal).toBeInstanceOf(AbortSignal);

        return { data: { object: 'list', data: [] }, config, status: 200, statusText: 'OK', headers: {} };
    };

    await queryClient.fetchQuery(serverFilesQueryOptions('alpha', '/nested'));
    const core = await queryClient.fetchQuery(panelFiles('alpha', '/nested'));

    expect(core).toEqual({ object: 'list', data: [] });
    expect(requests).toBe(1);
});

it('reads live state and usage from the entry the dashboard polls', async () => {
    let requests = 0;
    const resources = {
        object: 'stats',
        attributes: {
            current_state: 'running' as const,
            is_suspended: false,
            resources: {
                memory_bytes: 1,
                cpu_absolute: 2,
                disk_bytes: 3,
                network_rx_bytes: 4,
                network_tx_bytes: 5,
                uptime: 6,
            },
        },
    };

    http.defaults.adapter = async (config) => {
        requests++;
        expect(new URL(config.url!, 'https://panel.test').pathname).toBe('/api/client/servers/alpha/resources');

        return { data: resources, config, status: 200, statusText: 'OK', headers: {} };
    };

    const options = serverResourcesQueryOptions('alpha');
    const state: 'offline' | 'stopped' | 'starting' | 'running' | 'stopping' = (await queryClient.fetchQuery(options))
        .attributes.current_state;
    const core = await queryClient.fetchQuery(panelResources('alpha'));

    expect(state).toBe('running');
    expect(core).toEqual(resources);
    expect(requests).toBe(1);
    expect(options.retry).toBe(false);

    queryClient.setQueryData(serverResourcesQueryOptions('beta').queryKey, resources);
    await invalidateServerData('alpha', ['resources']);
    expect(queryClient.getQueryState(options.queryKey)?.isInvalidated).toBe(true);
    expect(queryClient.getQueryState(serverResourcesQueryOptions('beta').queryKey)?.isInvalidated).toBe(false);
});

it('reads bounded server logs through the shared transport', async () => {
    const requests: string[] = [];

    http.defaults.adapter = async (config) => {
        const url = new URL(config.url!, 'https://panel.test');

        expect(url.pathname).toBe('/api/client/servers/alpha/logs');
        expect(config.signal).toBeInstanceOf(AbortSignal);
        requests.push(url.searchParams.get('lines') ?? '');
        const data = { object: 'server_logs', attributes: { lines: ['first', 'second'] } };

        return { data, config, status: 200, statusText: 'OK', headers: {} };
    };

    const logs = await queryClient.fetchQuery(serverLogsQueryOptions('alpha', 25));

    await queryClient.fetchQuery({ ...serverLogsQueryOptions('alpha', 25), staleTime: Infinity });
    await queryClient.fetchQuery(serverLogsQueryOptions('alpha', 5000));
    await queryClient.fetchQuery(serverLogsQueryOptions('alpha', 0));

    expect(logs.attributes.lines).toEqual(['first', 'second']);
    expect(requests).toEqual(['25', '100', '1']);
});

it('inherits the client retry policy unless the panel query sets its own', () => {
    const files = serverFilesQueryOptions('alpha', '/');
    const startup = serverStartupQueryOptions('alpha');
    const config = { headers: new AxiosHeaders() };
    const forbidden = new AxiosError('Forbidden', AxiosError.ERR_BAD_REQUEST, config, undefined, {
        status: 403,
        statusText: '',
        headers: {},
        config,
        data: {},
    });

    expect('retry' in files).toBe(false);
    expect(queryClient.defaultQueryOptions(files).retry).toBe(queryClient.getDefaultOptions().queries?.retry);
    expect(queryClient.defaultQueryOptions(startup).retry).toBe(startup.retry);
    if (!(startup.retry instanceof Function)) {
        throw new Error('Startup reads set their own retry policy.');
    }

    expect(startup.retry(0, forbidden)).toBe(false);
    expect(startup.retry(2, new Error('offline'))).toBe(true);
});

const alpha = {
    object: 'server',
    attributes: { uuid: 'uuid-alpha', identifier: 'alpha', name: 'Alpha' },
} as Server;
const beta = { object: 'server', attributes: { uuid: 'uuid-beta', identifier: 'beta', name: 'Beta' } } as Server;
const coreCopy = () => panelServer('alpha').queryKey;
const extensionCopy = () => serverQueryOptions('uuid-alpha').queryKey;
const otherServer = () => panelServer('beta').queryKey;
const seedServerCopies = () => {
    queryClient.setQueryData(coreCopy(), alpha);
    queryClient.setQueryData(extensionCopy(), alpha);
    queryClient.setQueryData(otherServer(), beta);
};

it.each(['uuid-alpha', 'alpha'])('invalidates every cached copy of a server given %s', async (id) => {
    seedServerCopies();

    await invalidateServerData(id, ['server']);

    expect(queryClient.getQueryState(coreCopy())?.isInvalidated).toBe(true);
    expect(queryClient.getQueryState(extensionCopy())?.isInvalidated).toBe(true);
    expect(queryClient.getQueryState(otherServer())?.isInvalidated).toBe(false);
});

it('applies core server updates to the copy an extension keyed by uuid', () => {
    seedServerCopies();

    updateServerQueryData(queryClient, 'alpha', (current) => ({
        ...current,
        attributes: { ...current.attributes, name: 'Renamed' },
    }));

    expect(queryClient.getQueryData(coreCopy())?.attributes.name).toBe('Renamed');
    expect(queryClient.getQueryData(extensionCopy())?.attributes.name).toBe('Renamed');
    expect(queryClient.getQueryData(otherServer())?.attributes.name).toBe('Beta');
});

it('keys server logs by server and line count with a default of the maximum', () => {
    expect(serverLogsQueryOptions('alpha').queryKey).toEqual(serverLogsQueryOptions('alpha', 100).queryKey);
    expect(serverLogsQueryOptions('alpha', 250).queryKey).toEqual(serverLogsQueryOptions('alpha', 100).queryKey);
    expect(serverLogsQueryOptions('alpha', 10).queryKey).not.toEqual(serverLogsQueryOptions('alpha', 20).queryKey);
    expect(serverLogsQueryOptions('alpha', 10).queryKey).not.toEqual(serverLogsQueryOptions('beta', 10).queryKey);
});
