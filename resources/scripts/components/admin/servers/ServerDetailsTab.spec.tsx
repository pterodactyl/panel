/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type * as ServerQueries from '@/api/admin/servers/queries';
import type * as UserQueries from '@/api/admin/users/queries';
import type { SelectProps } from '@/components/ui/Select';
import ServerDetailsTab from './ServerDetailsTab';

const user = (id: number, name: string) => ({
    object: 'user',
    attributes: {
        id,
        email: `${name}@example.com`,
        image: `https://example.com/${name}`,
        first_name: name,
        last_name: 'Example',
        username: name,
    },
});

const alice = user(1, 'alice');
const bob = user(2, 'bob');
const carol = user(3, 'carol');

const server = {
    attributes: {
        id: 9,
        name: 'Survival',
        user: 1,
        external_id: null,
        description: null,
        updated_at: '2026-10-01T00:00:00Z',
        container: { installed: 1 },
        relationships: { user: alice },
    },
};

vi.mock('@/extensions/useExtensionFormFields', () => ({
    useExtensionFormFields: () => ({ values: {}, ready: true, failed: false }),
}));

vi.mock('@/components/ui/Select', () => ({
    default: ({ id, value, options = [], onChange, onSearchChange, multiple }: SelectProps) => (
        <>
            <input aria-label={'Search owners'} onChange={(event) => onSearchChange?.(event.currentTarget.value)} />
            <select
                data-testid={id}
                value={String(value ?? '')}
                onChange={(event) => {
                    const choice = options.find((option) => String(option.value) === event.currentTarget.value);
                    if (choice && !multiple) onChange(choice.value);
                }}
            >
                {options.map((option) => (
                    <option key={option.value} value={String(option.value)}>
                        {option.textLabel}
                    </option>
                ))}
            </select>
        </>
    ),
}));
vi.mock('@/plugins/useDebouncedValue', () => ({ useDebouncedValue: <T,>(value: T) => value }));
vi.mock('@/components/admin/servers/useServerDetail', () => ({
    useServerDetail: () => ({ server, reload: vi.fn() }),
}));
vi.mock('@/api/admin/servers/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof ServerQueries>()),
    useUpdateAdminServerDetails: () => ({ mutateAsync: vi.fn() }),
}));
vi.mock('@/api/admin/users/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof UserQueries>()),
    useAdminUsers: ({ filters }: { filters: { search: string } }) => ({
        data: { data: filters.search === 'carol' ? [carol] : [alice, bob] },
        isFetching: false,
    }),
}));

afterEach(cleanup);

describe('ServerDetailsTab', () => {
    it('keeps a newly picked owner selectable after the owner search is cleared', async () => {
        const events = userEvent.setup();
        render(<ServerDetailsTab />);

        expect(screen.getByTestId('user')).toHaveValue('1');

        await events.type(screen.getByLabelText('Search owners'), 'carol');
        await events.selectOptions(screen.getByTestId('user'), '3');
        await events.clear(screen.getByLabelText('Search owners'));

        expect(screen.getByTestId('user')).toHaveValue('3');
        expect(screen.getByRole('option', { name: 'carol Example (carol@example.com)' })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: 'bob Example (bob@example.com)' })).toBeInTheDocument();
    });
});
