/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as AccountQueries from '@/api/account/queries';
import { createExtensionTestHost, renderView, type ExtensionTestHost } from '@/sdk/testing';
import { View } from '../View';

const mocks = vi.hoisted(() => ({
    apiKeys: vi.fn(),
    createApiKey: vi.fn(),
    deleteApiKey: vi.fn(),
    sshKeys: vi.fn(),
    deleteSshKey: vi.fn(),
    updatePassword: vi.fn(),
    twoFactorToken: vi.fn(),
    enableTwoFactor: vi.fn(),
}));

const mutation = (mutateAsync: (...args: never[]) => unknown) => ({ mutateAsync, isPending: false });

vi.mock('@/api/account/api-keys/queries', () => ({
    useAccountApiKeys: () => ({ data: mocks.apiKeys(), error: null, refetch: vi.fn() }),
    useCreateAccountApiKey: () => mutation(mocks.createApiKey),
    useDeleteAccountApiKey: () => mutation(mocks.deleteApiKey),
}));
vi.mock('@/api/account/ssh-keys/queries', () => ({
    useSSHKeys: () => ({ data: mocks.sshKeys(), error: null, refetch: vi.fn() }),
    useCreateSSHKey: () => mutation(vi.fn()),
    useDeleteSSHKey: () => mutation(mocks.deleteSshKey),
}));
vi.mock('@/api/account/two-factor/queries', () => ({
    useAccountTwoFactorToken: (enabled: boolean) => ({
        data: enabled ? mocks.twoFactorToken() : undefined,
        error: null,
        isFetching: false,
        refetch: vi.fn(),
    }),
    useEnableAccountTwoFactor: () => mutation(mocks.enableTwoFactor),
    useDisableAccountTwoFactor: () => mutation(vi.fn()),
}));
vi.mock('@/api/account/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof AccountQueries>()),
    useUpdateAccountPassword: () => mutation(mocks.updatePassword),
}));

let host: ExtensionTestHost | undefined;

beforeEach(() => {
    vi.clearAllMocks();
    mocks.apiKeys.mockReturnValue({ object: 'list', data: [] });
    mocks.sshKeys.mockReturnValue({ object: 'list', data: [] });
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

async function renderLive(children: ReactNode) {
    host = createExtensionTestHost({ path: '/account' });

    await act(async () => {
        render(children, { wrapper: host!.Wrapper });
    });
}

describe('account views from fixtures', () => {
    it('composes the overview from its cards, with the two-factor notice when required', () => {
        const { container, unmount } = renderView('page.account.overview', { fixture: 'twoFactorRequired' });

        for (const title of ['Update Password', 'Update Email Address', 'Two-Step Verification', '2-Factor Required']) {
            expect(container).toHaveTextContent(title);
        }

        unmount();
    });

    it('shows a list page error in place of its cards', () => {
        const { container, unmount } = renderView('page.account.api', { fixture: 'error' });

        expect(container).toHaveTextContent('Request failed with status code 500.');
        expect(container).not.toHaveTextContent('Create API Key');
        unmount();
    });

    it('lists keys in a sortable table with their last use', () => {
        const { container, unmount } = renderView('account.apiKeys.list');

        expect(container).toHaveTextContent('Deploy script');
        expect(container).toHaveTextContent('Never');
        expect(within(container).getByRole('button', { name: 'Sort Description ascending' })).toBeInTheDocument();
        unmount();
    });
});

describe('account.password', () => {
    it('validates the confirmation and submits the API body', async () => {
        mocks.updatePassword.mockResolvedValue({});
        await renderLive(<View name='account.password' />);

        expect(screen.getByLabelText('Current Password')).toHaveAttribute('autocomplete', 'current-password');
        expect(screen.getByLabelText('New Password')).toHaveAttribute('autocomplete', 'new-password');

        fireEvent.change(screen.getByLabelText('Current Password'), { target: { value: 'old secret' } });
        fireEvent.change(screen.getByLabelText('New Password'), { target: { value: 'new secret' } });
        fireEvent.change(screen.getByLabelText('Confirm New Password'), { target: { value: 'new secrets' } });

        expect(await screen.findByText('Password confirmation does not match the password you entered.')).toBeVisible();

        fireEvent.change(screen.getByLabelText('Confirm New Password'), { target: { value: 'new secret' } });
        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Update Password' }));
        });

        expect(mocks.updatePassword).toHaveBeenCalledWith({
            body: { current_password: 'old secret', password: 'new secret', password_confirmation: 'new secret' },
        });
    });
});

describe('account.twoFactor', () => {
    it('enables two-step verification and shows the recovery codes once', async () => {
        mocks.twoFactorToken.mockReturnValue({ data: { secret: 'JBSWY3DPEHPK3PXP', image_url_data: 'otpauth://x' } });
        mocks.enableTwoFactor.mockResolvedValue({ attributes: { tokens: ['aaaa-1111', 'bbbb-2222'] } });
        await renderLive(<View name='account.twoFactor' />);

        fireEvent.click(screen.getByRole('button', { name: 'Enable Two-Step' }));

        const dialog = await screen.findByRole('dialog');

        expect(within(dialog).getByText('JBSW Y3DP EHPK 3PXP')).toBeInTheDocument();
        expect(within(dialog).getByRole('button', { name: 'Enable' })).toBeDisabled();
        fireEvent.change(within(dialog).getByPlaceholderText('000000'), { target: { value: '123456' } });
        fireEvent.change(within(dialog).getByLabelText('Account Password'), { target: { value: 'secret' } });
        await act(async () => {
            fireEvent.click(within(dialog).getByRole('button', { name: 'Enable' }));
        });

        expect(mocks.enableTwoFactor).toHaveBeenCalledWith({ body: { code: '123456', password: 'secret' } });
        const recovery = (await screen.findByText('Two-Step Authentication Enabled')).closest('[role="dialog"]');

        expect(recovery).toHaveTextContent('aaaa-1111');
        expect(recovery).toHaveTextContent('bbbb-2222');
    });
});

describe('account API keys', () => {
    it('shows a created key once', async () => {
        mocks.createApiKey.mockResolvedValue({ attributes: { identifier: 'ptlc_abc' }, meta: { secret_token: 'xyz' } });
        await renderLive(<View name='account.apiKeys.create' />);

        fireEvent.change(screen.getByLabelText('Description'), { target: { value: 'Deploys' } });
        fireEvent.change(screen.getByLabelText('Allowed IPs'), { target: { value: '10.0.0.1\n10.0.0.2' } });
        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Create' }));
        });

        expect(mocks.createApiKey).toHaveBeenCalledWith({
            body: { description: 'Deploys', allowed_ips: ['10.0.0.1', '10.0.0.2'] },
        });
        expect(await screen.findByText('ptlc_abcxyz')).toBeInTheDocument();
        expect(screen.getByLabelText('Description')).toHaveValue('');

        fireEvent.click(screen.getByRole('button', { name: 'Close' }));
        await waitFor(() => expect(screen.queryByText('ptlc_abcxyz')).not.toBeInTheDocument());
    });

    it('deletes a key after confirmation', async () => {
        mocks.apiKeys.mockReturnValue({
            object: 'list',
            data: [
                {
                    object: 'api_key',
                    attributes: {
                        identifier: 'ptlc_abc',
                        description: 'Deploys',
                        allowed_ips: [],
                        last_used_at: null,
                        created_at: '2026-01-01T00:00:00Z',
                    },
                },
            ],
        });
        mocks.deleteApiKey.mockResolvedValue({});
        await renderLive(<View name='account.apiKeys.list' />);

        fireEvent.click(screen.getByRole('button', { name: 'Delete Deploys API key' }));
        await act(async () => {
            fireEvent.click(await screen.findByRole('button', { name: 'Delete Key' }));
        });

        expect(mocks.deleteApiKey).toHaveBeenCalledWith({ path: { identifier: 'ptlc_abc' } });
    });
});

describe('account SSH keys', () => {
    it('deletes a key after confirmation', async () => {
        mocks.sshKeys.mockReturnValue({
            object: 'list',
            data: [{ object: 'ssh_key', attributes: { name: 'Laptop', fingerprint: 'abc', created_at: '2026-01-01' } }],
        });
        mocks.deleteSshKey.mockResolvedValue({});
        await renderLive(<View name='account.sshKeys.list' />);

        expect(screen.getAllByText('SHA256:abc')).not.toHaveLength(0);
        fireEvent.click(screen.getByRole('button', { name: 'Delete Laptop' }));
        await act(async () => {
            fireEvent.click(await screen.findByRole('button', { name: 'Delete Key' }));
        });

        expect(mocks.deleteSshKey).toHaveBeenCalledWith({ body: { fingerprint: 'abc' } });
    });
});
