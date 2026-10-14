import { expect, it, vi } from 'vitest';
import { pageStatus } from './pageStatus';

interface Loaded {
    readonly id: number;
}

const loaded: Loaded = { id: 1 };
const query = (data: Loaded | undefined, error: Error | null = null) => ({ data, error, refetch: vi.fn() });

it('loads until every query has data', () => {
    expect(pageStatus(query(loaded), query(undefined))).toMatchObject({ loading: true, error: null });
    expect(pageStatus(query(loaded), query(loaded))).toMatchObject({ loading: false, error: null });
});

it('shows the error of a query that never loaded, and retries what failed', () => {
    const succeeded = query(loaded);
    const failed = query(undefined, new Error('Request failed with status code 500'));
    const status = pageStatus(succeeded, failed);

    expect(status).toMatchObject({ loading: false, error: 'Request failed with status code 500' });
    status.retry();
    expect(failed.refetch).toHaveBeenCalledOnce();
    expect(succeeded.refetch).not.toHaveBeenCalled();
});

it('keeps the loaded data on show when a refresh fails', () => {
    expect(pageStatus(query(loaded, new Error('Network Error')))).toMatchObject({ loading: false, error: null });
});
