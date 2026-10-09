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
import { prepareExtensions } from './registry';

vi.mock('@/bootstrap', async (importOriginal) => ({
    ...(await importOriginal<typeof BootstrapModule>()),
    getBootstrapExtensions: () => [{ id: 'probe', entry: '/probe.js' }],
}));

const originalAdapter = http.defaults.adapter;
let host: ReturnType<typeof createExtensionTestHost> | undefined;

afterEach(() => {
    cleanup();
    host?.dispose();
    prepareExtensions([]);
    http.defaults.adapter = originalAdapter;
});

it('extension controls participate in native validation, submission, and shared cache invalidation', async () => {
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
    let requests = 0;
    let savedPayload: unknown;

    http.defaults.adapter = async (config) => {
        if (config.url === '/api/admin/languages') {
            return { data: { en: 'English' }, config, status: 200, statusText: 'OK', headers: {} };
        }

        expect(config.method).toBe('put');
        expect(config.url).toBe('/api/admin/users/1');
        requests++;
        savedPayload = JSON.parse(config.data);

        return { data: user, config, status: 200, statusText: 'OK', headers: {} };
    };

    prepareExtensions([{ id: 'probe', entry: '/probe.js' }]);
    await loadExtensions(async () => ({
        default: definePterodactylExtension({
            setup({ slots }) {
                slots.register('panel.users.detail.form', ({ data }) => (
                    <data.form.AppField
                        name='username'
                        validators={{
                            onSubmit: ({ value }) => (value === 'extended' ? undefined : 'Use the extension username.'),
                        }}
                    >
                        {(field) => <field.TextField label='Extension username' />}
                    </data.form.AppField>
                ));
            },
        }),
    }));
    host = createExtensionTestHost({ path: '/panel/users/1', resource: { kind: 'admin.user', resource: user } });
    const userKey = adminUserWithServersQueryOptions(1).queryKey;

    host.queryClient.setQueryData(userKey, user);
    const Wrapper = host.Wrapper;

    render(
        <Wrapper>
            <UserDetailContainer />
        </Wrapper>
    );
    const field = await screen.findByLabelText('Extension username');

    fireEvent.change(field, { target: { value: '' } });
    fireEvent.click(screen.getAllByRole('button', { name: 'Save Changes' })[0]);
    await waitFor(() =>
        expect(screen.getAllByText(/username must be provided|Use the extension username/i).length).toBeGreaterThan(0)
    );
    expect(requests).toBe(0);
    fireEvent.change(field, { target: { value: 'extended' } });
    fireEvent.click(screen.getAllByRole('button', { name: 'Save Changes' })[0]);
    await waitFor(() => expect(requests).toBe(1));
    await waitFor(() => expect(host?.queryClient.getQueryState(userKey)?.isInvalidated).toBe(true));
    expect(savedPayload).toMatchObject({ username: 'extended', email: 'alex@example.test' });
    expect(requests).toBe(1);
}, 20_000);
