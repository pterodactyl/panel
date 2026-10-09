/** @vitest-environment jsdom */
import { act, cleanup, renderHook } from '@testing-library/react';
import { afterEach, expect, it } from 'vitest';
import { Provider, useServerStore } from '@/state/server';

afterEach(cleanup);

const upload = (name: string) => ({ name, loaded: 0, total: 10, abort: new AbortController() });
const renderFiles = () => renderHook(() => useServerStore((state) => state.files), { wrapper: Provider });

it('tracks same-named uploads by id and cancels only the chosen one', () => {
    const { result } = renderFiles();
    const first = upload('a.txt');
    const second = upload('a.txt');

    act(() => {
        result.current.pushFileUpload({ id: 'upload-1', data: first });
        result.current.pushFileUpload({ id: 'upload-2', data: second });
    });
    act(() => result.current.setUploadProgress({ id: 'upload-2', loaded: 5 }));
    expect(result.current.uploads).toEqual({ 'upload-1': first, 'upload-2': { ...second, loaded: 5 } });

    act(() => result.current.cancelFileUpload('upload-1'));
    expect(first.abort.signal.aborted).toBe(true);
    expect(second.abort.signal.aborted).toBe(false);
    expect(Object.keys(result.current.uploads)).toEqual(['upload-2']);

    act(() => result.current.removeFileUpload('upload-2'));
    expect(second.abort.signal.aborted).toBe(false);
    expect(result.current.uploads).toEqual({});
});

it('aborts and forgets every upload when cleared', () => {
    const { result } = renderFiles();
    const uploads = [upload('a.txt'), upload('b.txt')];

    act(() => {
        for (const [index, data] of uploads.entries()) {
            result.current.pushFileUpload({ id: `upload-${index}`, data });
        }
    });
    act(() => result.current.clearFileUploads());

    expect(uploads.map(({ abort }) => abort.signal.aborted)).toEqual([true, true]);
    expect(result.current.uploads).toEqual({});
});
