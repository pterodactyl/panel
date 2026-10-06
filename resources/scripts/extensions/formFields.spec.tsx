// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import { adminUserWithServersQueryOptions } from '@/api/admin/users/queries';
import http from '@/api/http';
import type * as BootstrapModule from '@/bootstrap';
import UserDetailContainer from '@/components/admin/users/UserDetailContainer';
import { createExtensionTestHost } from '@/sdk/testing';
import { definePterodactylExtension, type AdminUserFormSlotData } from '@/sdk';
import { loadExtensions } from './loader';
import { newExtensionFieldValues } from './useExtensionFormFields';
import { prepareExtensions } from './registry';

declare module '@/extensions/formFields' {
    interface ExtensionFormFieldMap {
        'admin.user': { probe: { plan: string }; broken: { tier: string } };
    }
}

vi.mock('@/bootstrap', async (importOriginal) => ({
    ...(await importOriginal<typeof BootstrapModule>()),
    getBootstrapExtensions: () => [
        { id: 'probe', entry: '/probe.js', forms: ['admin.user'] },
        { id: 'broken', entry: '/broken.js', forms: ['admin.user'] },
    ],
}));

const originalAdapter = http.defaults.adapter;
let host: ReturnType<typeof createExtensionTestHost> | undefined;
afterEach(() => {
    cleanup();
    host?.dispose();
    http.defaults.adapter = originalAdapter;
});

it('extension fields load and save with the native form, and fields whose values did not load stay hidden', async () => {
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
        },
    };
    let savedPayload: unknown;
    const loads: string[] = [];
    http.defaults.adapter = async (config) => {
        if (config.url === '/api/admin/languages')
            return { data: { en: 'English' }, config, status: 200, statusText: 'OK', headers: {} };
        if (config.method === 'get' && config.url?.startsWith('/api/admin/extensions/forms/')) {
            loads.push(config.url);
            return { data: { data: { probe: { plan: 'gold' } } }, config, status: 200, statusText: 'OK', headers: {} };
        }
        savedPayload = JSON.parse(config.data);
        return { data: user, config, status: 200, statusText: 'OK', headers: {} };
    };
    prepareExtensions([
        { id: 'probe', entry: '/probe.js', forms: ['admin.user'] },
        { id: 'broken', entry: '/broken.js', forms: ['admin.user'] },
    ]);
    await loadExtensions(async (url) => ({
        default: definePterodactylExtension({
            setup({ slots }) {
                if (url === '/broken.js') {
                    slots.register('panel.users.detail.form', ({ data }) => (
                        <data.form.AppField name={'extensions.broken.tier'} defaultValue={'basic'}>
                            {(field) => <field.TextField label={'Broken tier'} />}
                        </data.form.AppField>
                    ));
                    return;
                }
                slots.register('panel.users.detail.form', ({ data }) => (
                    <data.form.AppField name={'extensions.probe.plan'}>
                        {(field) => <field.TextField label={'Billing plan'} />}
                    </data.form.AppField>
                ));
            },
        }),
    }));
    host = createExtensionTestHost({ path: '/panel/users/1', resource: { kind: 'admin.user', resource: user } });
    host.queryClient.setQueryData(adminUserWithServersQueryOptions(1).queryKey, user);
    const Wrapper = host.Wrapper;
    render(
        <Wrapper>
            <UserDetailContainer />
        </Wrapper>
    );

    const field = await screen.findByLabelText('Billing plan');
    expect(field).toHaveProperty('value', 'gold');
    expect(screen.queryByLabelText('Broken tier')).toBeNull();
    expect(loads).toEqual(['/api/admin/extensions/forms/admin.user/1']);
    fireEvent.change(field, { target: { value: 'silver' } });
    fireEvent.click(screen.getAllByRole('button', { name: 'Save Changes' })[0]);

    await waitFor(() =>
        expect(savedPayload).toMatchObject({ username: 'alex', extensions: { probe: { plan: 'silver' } } })
    );
    expect(savedPayload).not.toHaveProperty('extensions.broken');
    expect(newExtensionFieldValues('admin.user')).toEqual({ probe: {}, broken: {} });
    expect(newExtensionFieldValues('admin.node')).toEqual({});
}, 20_000);
