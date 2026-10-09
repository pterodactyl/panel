/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterEach, expect, it } from 'vitest';
import { useOpenLoginCheckpoint } from './navigation';
import { createExtensionTestHost, type ExtensionTestHost } from './testing';

let host: ExtensionTestHost | undefined;

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

it('hands the confirmation token to the native checkpoint screen through history state', async () => {
    host = createExtensionTestHost({ path: '/auth/login' });
    let open: ((token: string) => Promise<void>) | undefined;

    function Probe() {
        open = useOpenLoginCheckpoint();

        return <span>ready</span>;
    }

    render(<Probe />, { wrapper: host.Wrapper });
    await screen.findByText('ready');

    await act(() => open!('checkpoint-token'));

    expect(host.location()).toMatchObject({
        pathname: '/auth/login/checkpoint',
        search: {},
        state: { token: 'checkpoint-token' },
    });
});

it('carries the post-login destination through to the checkpoint screen', async () => {
    host = createExtensionTestHost({ path: '/auth/login?redirect=%2Fpanel%2Fnodes' });
    let open: ((token: string) => Promise<void>) | undefined;

    function Probe() {
        open = useOpenLoginCheckpoint();

        return <span>ready</span>;
    }

    render(<Probe />, { wrapper: host.Wrapper });
    await screen.findByText('ready');

    await act(() => open!('checkpoint-token'));

    expect(host.location()).toMatchObject({
        pathname: '/auth/login/checkpoint',
        search: { redirect: '/panel/nodes' },
    });
});
