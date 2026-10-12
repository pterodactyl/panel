import { useMemo, useState } from 'react';
import { useField, useForm, useStore } from '@tanstack/react-form';
import {
    useAccountApiKeys,
    useCreateAccountApiKey,
    useDeleteAccountApiKey,
    type ApiKey,
} from '@/api/account/api-keys/queries';
import { useCreateSSHKey, useDeleteSSHKey, useSSHKeys, type SSHKey } from '@/api/account/ssh-keys/queries';
import { httpErrorToHuman } from '@/api/http';
import { boundField, fixtureField } from '../forms';
import { VIEW_PAGES } from '../pages';
import { defineView } from '../registry';
import type {
    AccountListPageModel,
    ApiKeyCreateModel,
    ApiKeyItem,
    ApiKeyListModel,
    SshKeyCreateModel,
    SshKeyItem,
    SshKeyListModel,
} from './types';

const noop = () => {};

function listPageModel({ error, refetch }: { error: Error | null; refetch: () => unknown }): AccountListPageModel {
    return { error: error ? httpErrorToHuman(error) : null, retry: () => void refetch() };
}

const useApiKeysPageModel = () => listPageModel(useAccountApiKeys());
const useSshKeysPageModel = () => listPageModel(useSSHKeys());

const toApiKeyItem = ({ attributes }: ApiKey): ApiKeyItem => ({
    identifier: attributes.identifier,
    description: attributes.description,
    allowedIps: attributes.allowed_ips ?? [],
    lastUsedAt: attributes.last_used_at,
    createdAt: attributes.created_at,
});

const toSshKeyItem = ({ attributes }: SSHKey): SshKeyItem => ({
    name: attributes.name,
    fingerprint: attributes.fingerprint,
    createdAt: attributes.created_at,
});

function useApiKeyListModel(): ApiKeyListModel {
    const { data } = useAccountApiKeys();
    const { mutateAsync, isPending } = useDeleteAccountApiKey();

    return useMemo(
        () => ({
            keys: (data?.data ?? []).map(toApiKeyItem),
            loading: !data,
            deleting: isPending,
            remove: (identifier: string) => mutateAsync({ path: { identifier } }).then(noop, noop),
        }),
        [data, isPending, mutateAsync]
    );
}

function useApiKeyCreateModel(): ApiKeyCreateModel {
    const [createdKey, setCreatedKey] = useState<string | null>(null);
    const createApiKey = useCreateAccountApiKey();
    const form = useForm({
        defaultValues: { description: '', allowedIps: '' },
        onSubmit: async ({ value, formApi }) => {
            try {
                const created = await createApiKey.mutateAsync({
                    body: {
                        description: value.description,
                        allowed_ips: value.allowedIps.length > 0 ? value.allowedIps.split('\n') : [],
                    },
                });

                formApi.reset();
                setCreatedKey(`${created.attributes.identifier}${created.meta?.secret_token ?? ''}`);
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const description = useField({
        form,
        name: 'description',
        validators: {
            onChange: ({ value }) =>
                value.length >= 4 ? undefined : 'A description of at least 4 characters must be provided.',
        },
    });
    const allowedIps = useField({ form, name: 'allowedIps' });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: { description: boundField(description, { type: 'text' }), allowedIps: boundField(allowedIps) },
        submit: () => void form.handleSubmit(),
        canSubmit,
        submitting,
        createdKey,
        dismissCreatedKey: () => setCreatedKey(null),
    };
}

function useSshKeyListModel(): SshKeyListModel {
    const { data } = useSSHKeys();
    const { mutateAsync } = useDeleteSSHKey();

    return useMemo(
        () => ({
            keys: (data?.data ?? []).map(toSshKeyItem),
            loading: !data,
            remove: (fingerprint: string) => mutateAsync({ body: { fingerprint } }).then(noop, noop),
        }),
        [data, mutateAsync]
    );
}

function useSshKeyCreateModel(): SshKeyCreateModel {
    const createSshKey = useCreateSSHKey();
    const form = useForm({
        defaultValues: { name: '', publicKey: '' },
        onSubmit: async ({ value, formApi }) => {
            try {
                await createSshKey.mutateAsync({ body: { name: value.name, public_key: value.publicKey } });
                formApi.reset();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const name = useField({
        form,
        name: 'name',
        validators: { onChange: ({ value }) => (value.length >= 1 ? undefined : 'A key name must be provided.') },
    });
    const publicKey = useField({
        form,
        name: 'publicKey',
        validators: { onChange: ({ value }) => (value.length >= 1 ? undefined : 'A public key must be provided.') },
    });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: { name: boundField(name, { type: 'text' }), publicKey: boundField(publicKey) },
        submit: () => void form.handleSubmit(),
        canSubmit,
        submitting,
    };
}

const apiKeys: readonly ApiKeyItem[] = [
    {
        identifier: 'ptlc_7hX2kQ9vLm',
        description: 'Deploy script',
        allowedIps: ['203.0.113.10'],
        lastUsedAt: '2026-10-11T18:24:00Z',
        createdAt: '2026-03-02T09:15:00Z',
    },
    {
        identifier: 'ptlc_c4Rt8bZ1Yw',
        description: 'Home Assistant',
        allowedIps: [],
        lastUsedAt: null,
        createdAt: '2026-08-19T21:40:00Z',
    },
];

const sshKeys: readonly SshKeyItem[] = [
    { name: 'Laptop', fingerprint: 'W1kFq9n8Zt2c6Vb0RkM5yXo3sLe7uPj4hAdGiQ', createdAt: '2026-02-14T10:05:00Z' },
    { name: 'Build server', fingerprint: 'k3Nf8Qz2Lx7Wc1Vy5Rb9Tm4Hs6Ju0Pe2Ga8DiO', createdAt: '2026-09-30T16:42:00Z' },
];

const apiKeyListFixture = (overrides: Partial<ApiKeyListModel> = {}): ApiKeyListModel => ({
    keys: apiKeys,
    loading: false,
    deleting: false,
    remove: async () => {},
    ...overrides,
});

const sshKeyListFixture = (overrides: Partial<SshKeyListModel> = {}): SshKeyListModel => ({
    keys: sshKeys,
    loading: false,
    remove: async () => {},
    ...overrides,
});

const apiKeyCreateFixture = (createdKey: string | null = null): ApiKeyCreateModel => ({
    fields: { description: fixtureField('description', { type: 'text' }), allowedIps: fixtureField('allowedIps') },
    submit: noop,
    canSubmit: true,
    submitting: false,
    createdKey,
    dismissCreatedKey: noop,
});

const listPageFixtures = {
    default: (): AccountListPageModel => ({ error: null, retry: noop }),
    error: (): AccountListPageModel => ({ error: 'Request failed with status code 500.', retry: noop }),
};

export const keyViews = [
    defineView('page.account.api', {
        ...VIEW_PAGES['page.account.api'],
        use: useApiKeysPageModel,
        fixtures: listPageFixtures,
    }),
    defineView('page.account.ssh', {
        ...VIEW_PAGES['page.account.ssh'],
        use: useSshKeysPageModel,
        fixtures: listPageFixtures,
    }),
    defineView('account.apiKeys.list', {
        context: 'account',
        use: useApiKeyListModel,
        fixtures: {
            keys: () => apiKeyListFixture(),
            empty: () => apiKeyListFixture({ keys: [] }),
            loading: () => apiKeyListFixture({ keys: [], loading: true }),
            deleting: () => apiKeyListFixture({ deleting: true }),
        },
    }),
    defineView('account.apiKeys.create', {
        context: 'account',
        use: useApiKeyCreateModel,
        fixtures: {
            default: () => apiKeyCreateFixture(),
            invalid: () => {
                const model = apiKeyCreateFixture();

                return {
                    ...model,
                    fields: {
                        ...model.fields,
                        description: fixtureField(
                            'description',
                            { type: 'text' },
                            'ci',
                            'A description of at least 4 characters must be provided.'
                        ),
                    },
                    canSubmit: false,
                };
            },
            created: () => apiKeyCreateFixture('ptlc_9mQ4rT2xVbN8kLw3Zp7Hy1Jc6Fs0Dg5Ae'),
        },
    }),
    defineView('account.sshKeys.list', {
        context: 'account',
        use: useSshKeyListModel,
        fixtures: {
            keys: () => sshKeyListFixture(),
            empty: () => sshKeyListFixture({ keys: [] }),
            loading: () => sshKeyListFixture({ keys: [], loading: true }),
        },
    }),
    defineView('account.sshKeys.create', {
        context: 'account',
        use: useSshKeyCreateModel,
        fixtures: {
            default: () => ({
                fields: { name: fixtureField('name', { type: 'text' }), publicKey: fixtureField('publicKey') },
                submit: noop,
                canSubmit: true,
                submitting: false,
            }),
            submitting: () => ({
                fields: {
                    name: fixtureField('name', { type: 'text' }, 'Laptop'),
                    publicKey: fixtureField('publicKey', {}, 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMq alex@laptop'),
                },
                submit: noop,
                canSubmit: false,
                submitting: true,
            }),
        },
    }),
];
