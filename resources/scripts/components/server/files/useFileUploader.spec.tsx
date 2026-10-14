/** @vitest-environment jsdom */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, renderHook } from '@testing-library/react';
import type { PropsWithChildren } from 'react';
import { toast } from 'sonner';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type * as FileQueries from '@/api/server/files/queries';
import { serverQueryOptions } from '@/api/server/queries';
import type * as RouterParams from '@/router/params';
import type * as ServerState from '@/state/server';
import { Provider } from '@/state/server';
import { createTestServer } from '@/views/fixtures';
import useFileUploader from './useFileUploader';

const upload = vi.hoisted(() => vi.fn());

vi.mock('@/router/params', async (importOriginal) => ({
    ...(await importOriginal<typeof RouterParams>()),
    useServerRouteId: () => 'test-server',
}));
vi.mock('@/state/server', async (importOriginal) => ({
    ...(await importOriginal<typeof ServerState>()),
    useServerDirectory: () => '/',
}));
vi.mock('@/api/server/files/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof FileQueries>()),
    useUploadFiles: () => ({ mutateAsync: upload }),
}));

const mebibyte = 1024 * 1024;
const server = createTestServer();
let client: QueryClient;

const file = (name: string, size: number) => new File([new Uint8Array(size)], name, { type: 'application/zip' });

const renderUploader = () =>
    renderHook(() => useFileUploader(), {
        wrapper: ({ children }: PropsWithChildren) => (
            <QueryClientProvider client={client}>
                <Provider>{children}</Provider>
            </QueryClientProvider>
        ),
    });

beforeEach(() => {
    client = new QueryClient({ defaultOptions: { queries: { staleTime: Infinity, retry: false } } });
    client.setQueryData(serverQueryOptions('test-server').queryKey, {
        ...server,
        attributes: { ...server.attributes, upload_size: 1 },
    });
    upload.mockReset().mockResolvedValue({ uploaded: [], cancelled: [], failed: [] });
});

afterEach(() => {
    cleanup();
    client.clear();
    vi.restoreAllMocks();
});

it('refuses a file over the node upload limit before sending anything', async () => {
    const error = vi.spyOn(toast, 'error');
    const { result } = renderUploader();

    await act(async () => {
        await expect(result.current([file('small.zip', mebibyte), file('world.zip', mebibyte + 1)])).rejects.toThrow(
            'world.zip is larger than the 1 MiB upload limit.'
        );
    });

    expect(error).toHaveBeenCalledExactlyOnceWith('world.zip is larger than the 1 MiB upload limit.');
    expect(upload).not.toHaveBeenCalled();
});

it('counts every file over the limit', async () => {
    const error = vi.spyOn(toast, 'error');
    const { result } = renderUploader();

    await act(async () => {
        await expect(
            result.current([file('a.zip', 2 * mebibyte), file('b.zip', 10), file('c.zip', 3 * mebibyte)])
        ).rejects.toThrow('2 files are larger than the 1 MiB upload limit.');
    });

    expect(error).toHaveBeenCalledExactlyOnceWith('2 files are larger than the 1 MiB upload limit.');
    expect(upload).not.toHaveBeenCalled();
});

it('uploads files up to the limit', async () => {
    const error = vi.spyOn(toast, 'error');
    const files = [file('a.zip', mebibyte), file('b.zip', 10)];
    const { result } = renderUploader();

    await act(() => result.current(files));

    expect(error).not.toHaveBeenCalled();
    expect(upload).toHaveBeenCalledExactlyOnceWith(expect.objectContaining({ uuid: server.attributes.uuid, files }));
});
