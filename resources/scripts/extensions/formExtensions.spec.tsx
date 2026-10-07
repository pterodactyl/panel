// @vitest-environment jsdom
import { AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { adminUserWithServersQueryOptions } from '@/api/admin/users/queries';
import http from '@/api/http';
import type * as BootstrapModule from '@/bootstrap';
import CreateUserForm from '@/components/admin/users/CreateUserForm';
import UserDetailContainer from '@/components/admin/users/UserDetailContainer';
import { createExtensionTestHost } from '@/sdk/testing';
import { definePterodactylExtension, type AdminUserFormSlotData, type FormExtensionProps } from '@/sdk';
import { loadExtensions } from './loader';
import { createExtensionRegistryBatch, prepareExtensions, registerFormExtension } from './registry';

vi.mock('@/bootstrap', async (importOriginal) => ({
    ...(await importOriginal<typeof BootstrapModule>()),
    getBootstrapExtensions: () => [
        { id: 'probe', entry: '/probe.js' },
        { id: 'other', entry: '/other.js' },
    ],
    getBootstrapExtensionForms: () => ({
        'admin.user': [
            { id: 'probe', name: 'Probe' },
            { id: 'other', name: 'Other' },
            { id: 'unloaded', name: 'Unloaded' },
        ],
    }),
}));

type ProbeValues = { tier: string; note: string };

function ProbeFields({ field, values, mode }: FormExtensionProps<ProbeValues, 'admin.user'>) {
    const tier = field('tier');

    return (
        <div>
            <label htmlFor={tier.id}>Tier</label>
            <input id={tier.id} value={tier.value ?? ''} onChange={(event) => tier.setValue(event.target.value)} />
            {tier.error && <p role={'alert'}>{tier.error}</p>}
            <p>
                {mode} note: {values.note ?? 'none'}
            </p>
        </div>
    );
}

function OtherFields() {
    return <p>other fields</p>;
}

const user: AdminUserFormSlotData['resource'] = {
    object: 'user',
    attributes: {
        id: 1,
        external_id: null,
        uuid: 'user-1',
        username: 'alex',
        email: 'alex@example.test',
        first_name: 'Alex',
        last_name: 'User',
        language: 'en',
        root_admin: false,
        '2fa': false,
        image: '',
        servers_count: 0,
        subuser_of_count: 0,
        created_at: '2026-10-02T00:00:00Z',
        updated_at: '2026-10-02T00:00:00Z',
        // `unloaded` is missing: the panel could not read its values.
        extensions: { probe: { tier: 'gold', note: 'vip' }, other: { flag: true } },
    },
};

const originalAdapter = http.defaults.adapter;
let host: ReturnType<typeof createExtensionTestHost> | undefined;
afterEach(() => {
    cleanup();
    host?.dispose();
    prepareExtensions([]);
    http.defaults.adapter = originalAdapter;
});

type ResponseBody = Record<string, string> | { errors: { detail: string; meta: { source_field: string } }[] };

const respond = (config: InternalAxiosRequestConfig, status: number, data: ResponseBody): AxiosResponse => ({
    data,
    config,
    status,
    statusText: String(status),
    headers: {},
});

async function boot() {
    prepareExtensions([
        { id: 'probe', entry: '/probe.js' },
        { id: 'other', entry: '/other.js' },
    ]);
    await loadExtensions(async (url) => ({
        default: definePterodactylExtension({
            setup({ forms }) {
                if (url === '/probe.js') forms.extend('admin.user', ProbeFields);
                else forms.extend('admin.user', OtherFields);
            },
        }),
    }));
}

describe('extension fields in admin forms', () => {
    it('loads saved values into the extension, sends only extensions that changed and shows the panel messages', async () => {
        const payloads: unknown[] = [];
        http.defaults.adapter = async (config) => {
            if (config.url === '/api/admin/languages') return respond(config, 200, { en: 'English' });
            payloads.push(JSON.parse(config.data));
            const errors = [{ detail: 'Pick a tier we sell.', meta: { source_field: 'extensions.probe.tier' } }];
            throw new AxiosError(
                'Request failed with status code 422',
                'ERR_BAD_REQUEST',
                config,
                null,
                respond(config, 422, { errors })
            );
        };
        await boot();
        host = createExtensionTestHost({ path: '/panel/users/1', resource: { kind: 'admin.user', resource: user } });
        host.queryClient.setQueryData(adminUserWithServersQueryOptions(1).queryKey, user);
        const Wrapper = host.Wrapper;
        render(
            <Wrapper>
                <UserDetailContainer />
            </Wrapper>
        );

        const tier = await screen.findByLabelText('Tier');
        expect(tier).toHaveProperty('value', 'gold');
        expect(screen.getByText('edit note: vip')).toBeTruthy();
        expect(screen.getByText('other fields')).toBeTruthy();
        expect(screen.queryByText('Unloaded')).toBeNull();

        const save = () => fireEvent.click(screen.getAllByRole('button', { name: 'Save Changes' })[0]);
        save();
        await waitFor(() => expect(payloads).toHaveLength(1));
        expect(payloads[0]).toMatchObject({ extensions: {} });

        fireEvent.change(tier, { target: { value: 'platinum' } });
        save();
        await waitFor(() => expect(payloads).toHaveLength(2));
        expect((payloads[1] as { extensions: unknown }).extensions).toEqual({
            probe: { tier: 'platinum', note: 'vip' },
        });

        expect((await screen.findByRole('alert')).textContent).toBe('Pick a tier we sell.');
        fireEvent.change(tier, { target: { value: 'gold' } });
        expect(screen.queryByRole('alert')).toBeNull();
    }, 20_000);

    it('starts a create form with no values and sends what was entered', async () => {
        const payloads: unknown[] = [];
        http.defaults.adapter = async (config) => {
            if (config.url === '/api/admin/languages') return respond(config, 200, { en: 'English' });
            payloads.push(JSON.parse(config.data));
            throw new AxiosError(
                'Request failed with status code 422',
                'ERR_BAD_REQUEST',
                config,
                null,
                respond(config, 422, { errors: [] })
            );
        };
        await boot();
        host = createExtensionTestHost({ path: '/panel/users/new' });
        const Wrapper = host.Wrapper;
        render(
            <Wrapper>
                <CreateUserForm />
            </Wrapper>
        );

        const tier = await screen.findByLabelText('Tier');
        expect(tier).toHaveProperty('value', '');
        expect(screen.getByText('create note: none')).toBeTruthy();
        expect(screen.queryByText('Unloaded')).toBeNull();

        fireEvent.change(screen.getByLabelText('Email Address'), { target: { value: 'new@example.test' } });
        fireEvent.change(screen.getByLabelText('Username'), { target: { value: 'newuser' } });
        fireEvent.change(screen.getByLabelText('First Name'), { target: { value: 'New' } });
        fireEvent.change(screen.getByLabelText('Last Name'), { target: { value: 'User' } });
        fireEvent.change(tier, { target: { value: 'silver' } });
        fireEvent.click(screen.getByRole('button', { name: 'Create User' }));

        await waitFor(() => expect(payloads).toHaveLength(1));
        expect((payloads[0] as { extensions: unknown }).extensions).toEqual({ probe: { tier: 'silver' } });
    }, 20_000);
});

describe('form extension registration', () => {
    it('refuses unknown forms and a second component for the same form', () => {
        const batch = createExtensionRegistryBatch();
        registerFormExtension({ extensionId: 'probe', form: 'admin.user', component: OtherFields }, batch);

        expect(() =>
            registerFormExtension({ extensionId: 'probe', form: 'admin.user', component: OtherFields }, batch)
        ).toThrow('Duplicate component for the "admin.user" form.');
        expect(() =>
            registerFormExtension(
                { extensionId: 'probe', form: 'admin.users' as 'admin.user', component: OtherFields },
                batch
            )
        ).toThrow('Unknown form "admin.users".');
    });
});
