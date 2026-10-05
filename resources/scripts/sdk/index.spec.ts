// @vitest-environment jsdom
import { renderHook } from '@testing-library/react';
import { expect, it, vi } from 'vitest';
import { useServerPermission } from './index';

vi.mock('@/api/server/queries', () => ({
    useCurrentServerPermissions: () => ['ext.polls.view', 'file.read'],
}));

it('keeps extension wildcards within their namespace in the public permission hook', () => {
    expect(renderHook(() => useServerPermission('ext.votes.*')).result.current).toBe(false);
    expect(renderHook(() => useServerPermission(['ext.votes.*', 'file.read'], true)).result.current).toBe(true);
    expect(renderHook(() => useServerPermission(['ext.polls.*', 'file.read'])).result.current).toBe(true);
});
