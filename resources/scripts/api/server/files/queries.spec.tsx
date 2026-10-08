// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, renderHook, waitFor } from '@testing-library/react';
import axios, { type AxiosAdapter, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import {
    chmodFilesInput,
    renameFilesInput,
    serverFileContentQueryOptions,
    serverFilesQueryKey,
    useChmodFiles,
    useRenameFiles,
    useUploadFiles,
    type FileUploadOutcome,
    type FileUploadSummary,
} from './queries';

const mocks = vi.hoisted(() => ({ error: vi.fn(), success: vi.fn() }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.error }));
vi.mock('sonner', () => ({ toast: { success: mocks.success, error: vi.fn() } }));

interface Post {
    name: string;
    finish: () => void;
    fail: () => void;
}

const respond = <T,>(config: InternalAxiosRequestConfig, data: T, status = 200): AxiosResponse<T> => ({
    config,
    data,
    headers: {},
    status,
    statusText: 'OK',
});
const originalHttpAdapter = http.defaults.adapter;
let client: QueryClient;
let posts: Post[];
let queued: { id: string; name: string; controller: AbortController }[];
let settled: [string, FileUploadOutcome][];
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);
const listing = serverFilesQueryKey('server', '/config');
const callbacks = {
    onFileQueued: (id: string, file: File, controller: AbortController) =>
        queued.push({ id, name: file.name, controller }),
    onFileProgress: vi.fn(),
    onFileSettled: (id: string, outcome: FileUploadOutcome) => settled.push([id, outcome]),
};
const file = (name: string) => new File([name], name, { type: 'text/plain' });

function upload(files: File[]) {
    const { result } = renderHook(() => useUploadFiles(), { wrapper });
    let done: Promise<FileUploadSummary> | undefined;
    act(() => {
        done = result.current.mutateAsync({ uuid: 'server', directory: '/config', files, callbacks });
    });
    return done!;
}

beforeEach(() => {
    vi.clearAllMocks();
    posts = [];
    queued = [];
    settled = [];
    client = new QueryClient({ defaultOptions: { queries: { staleTime: 30_000, retry: false } } });
    client.setQueryData(listing, { object: 'list', data: [] });
    http.defaults.adapter = (async (config) =>
        config.url?.endsWith('/files/upload')
            ? respond(config, { object: 'signed_url', attributes: { url: 'https://wings.test/upload/file' } })
            : respond(config, '', 204)) satisfies AxiosAdapter;
    vi.spyOn(axios, 'post').mockImplementation(
        (url, data, config) =>
            new Promise((resolve, reject) => {
                expect(url).toBe('https://wings.test/upload/file');
                expect(config?.params).toEqual({ directory: '/config' });
                config?.signal?.addEventListener?.('abort', () => reject(new axios.CanceledError()));
                posts.push({
                    name: (data as { files: File }).files.name,
                    finish: () => resolve(''),
                    fail: () => reject(new axios.AxiosError('Wings failed', 'ERR_BAD_RESPONSE')),
                });
            })
    );
});

afterEach(() => {
    cleanup();
    client.clear();
    http.defaults.adapter = originalHttpAdapter;
    vi.restoreAllMocks();
});

describe('useUploadFiles', () => {
    it('uploads at most three files at once and tracks same-named files apart', async () => {
        const done = upload([file('a.txt'), file('a.txt'), file('b.txt'), file('c.txt')]);

        await waitFor(() => expect(posts).toHaveLength(3));
        expect(queued.map((upload) => upload.name)).toEqual(['a.txt', 'a.txt', 'b.txt', 'c.txt']);
        expect(new Set(queued.map((upload) => upload.id)).size).toBe(4);

        posts[0].finish();
        await waitFor(() => expect(posts).toHaveLength(4));
        posts.slice(1).forEach((post) => post.finish());

        const summary = await done;
        expect(summary.uploaded.map((uploaded) => uploaded.name)).toEqual(['a.txt', 'a.txt', 'b.txt', 'c.txt']);
        expect(settled.map(([, outcome]) => outcome)).toEqual(['uploaded', 'uploaded', 'uploaded', 'uploaded']);
        expect(mocks.success).toHaveBeenCalledWith('Upload complete');
        expect(client.getQueryState(listing)?.isInvalidated).toBe(true);
    });

    it('settles a cancelled and a failed file on their own and still refreshes the listing', async () => {
        const done = upload([file('a.txt'), file('b.txt'), file('c.txt')]);
        await waitFor(() => expect(posts).toHaveLength(3));

        queued[1].controller.abort();
        posts[2].fail();
        posts[0].finish();

        const summary = await done;
        expect(summary.uploaded.map((uploaded) => uploaded.name)).toEqual(['a.txt']);
        expect(summary.cancelled.map((cancelled) => cancelled.name)).toEqual(['b.txt']);
        expect(summary.failed.map((failed) => failed.name)).toEqual(['c.txt']);
        expect(new Map(settled)).toEqual(
            new Map([
                [queued[0].id, 'uploaded'],
                [queued[1].id, 'cancelled'],
                [queued[2].id, 'failed'],
            ])
        );
        expect(mocks.error).toHaveBeenCalledExactlyOnceWith(expect.anything(), 'Unable to upload c.txt');
        expect(mocks.success).not.toHaveBeenCalled();
        expect(client.getQueryState(listing)?.isInvalidated).toBe(true);
    });

    it('never starts a queued file cancelled before its turn', async () => {
        const done = upload([file('a.txt'), file('b.txt'), file('c.txt'), file('d.txt')]);
        await waitFor(() => expect(posts).toHaveLength(3));

        queued[3].controller.abort();
        posts.forEach((post) => post.finish());

        const summary = await done;
        expect(posts.map((post) => post.name)).toEqual(['a.txt', 'b.txt', 'c.txt']);
        expect(summary.cancelled.map((cancelled) => cancelled.name)).toEqual(['d.txt']);
        expect(mocks.error).not.toHaveBeenCalled();
    });
});

describe('file queries', () => {
    it('refreshes the destination listing after a move', async () => {
        const destination = serverFilesQueryKey('server', '/config/plugins');
        client.setQueryData(destination, { object: 'list', data: [] });
        const { result } = renderHook(() => useRenameFiles(), { wrapper });

        await act(() =>
            result.current.mutateAsync(renameFilesInput('server', '/config', [{ from: 'b.txt', to: 'plugins/b.txt' }]))
        );

        expect(client.getQueryState(destination)?.isInvalidated).toBe(true);
        expect(client.getQueryState(listing)?.isInvalidated).toBe(true);
    });

    it('sends chmod modes to wings as octal strings and patches the cached listing', async () => {
        let sent: unknown;
        http.defaults.adapter = (async (config) => {
            sent = JSON.parse(config.data as string);
            return respond(config, '', 204);
        }) satisfies AxiosAdapter;
        client.setQueryData(listing, {
            object: 'list',
            data: [
                {
                    object: 'file_object',
                    attributes: { name: 'a.txt', is_file: true, mode: '-rw-------', mode_bits: '0600' },
                },
            ],
        });
        const { result } = renderHook(() => useChmodFiles(), { wrapper });

        await act(() =>
            result.current.mutateAsync(chmodFilesInput('server', '/config', [{ file: 'a.txt', mode: '644' }]))
        );

        expect(sent).toEqual({ root: '/config', files: [{ file: 'a.txt', mode: '644' }] });
        expect(client.getQueryData<{ data: { attributes: object }[] }>(listing)?.data[0].attributes).toMatchObject({
            mode: '-rw-r--r--',
            mode_bits: '0644',
        });
    });

    it('treats cached file contents as stale', () => {
        expect(serverFileContentQueryOptions('server', '/config/app.yml').staleTime).toBe(0);
    });
});
