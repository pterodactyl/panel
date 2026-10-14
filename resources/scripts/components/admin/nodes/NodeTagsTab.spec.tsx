/** @vitest-environment jsdom */

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as TagQueries from '@/api/admin/tags/queries';
import type { SelectProps } from '@/components/ui/Select';
import NodeTagsTab from './NodeTagsTab';

const mocks = vi.hoisted(() => ({ calls: [] as string[] }));

const tag = (id: number, name: string): TagQueries.AdminTag => ({
    object: 'tag',
    attributes: {
        id,
        name,
        slug: name.toLowerCase(),
        color: null,
        is_predefined: false,
        legacy_nest_id: null,
        eggs_count: 0,
        nodes_count: 0,
        created_at: '',
        updated_at: '',
    },
});

const minecraft = tag(1, 'Minecraft');
const premium = tag(2, 'Premium');
const sync = (kind: string) =>
    vi.fn(async ({ body }: { body: { tags: string[] } }) => {
        mocks.calls.push(`${kind}:${body.tags.join(',')}`);

        return { data: body.tags.map((id) => (id === '1' ? minecraft : premium)) };
    });

vi.mock('@/router/routeTree', () => ({ nodeDetailRoute: { useParams: () => ({ id: 4 }) } }));
vi.mock('@/api/admin/nodes/queries', () => ({ adminNodeTagsQueryOptions: () => ({ queryKey: ['node-tags'] }) }));
vi.mock('@/components/ui/Select', () => ({
    default: ({ id, value, options = [], onChange, multiple }: SelectProps) => (
        <select
            id={id}
            multiple={multiple}
            value={Array.isArray(value) ? value.map(String) : String(value ?? '')}
            onChange={(event) => {
                if (multiple) {
                    onChange(Array.from(event.currentTarget.selectedOptions, (option) => option.value));
                }
            }}
        >
            {options.map((option) => (
                <option key={option.value} value={String(option.value)}>
                    {String(option.label)}
                </option>
            ))}
        </select>
    ),
}));
vi.mock('@/api/admin/tags/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof TagQueries>()),
    useAllAdminTags: () => ({ data: [minecraft, premium], error: null, isPending: false, refetch: vi.fn() }),
    useSyncAdminNodeGameTags: () => ({ mutateAsync: sync('games') }),
    useSyncAdminNodeDeploymentTags: () => ({ mutateAsync: sync('reservations') }),
}));

const setup = () => {
    const queryClient = new QueryClient({ defaultOptions: { queries: { staleTime: Infinity } } });

    queryClient.setQueryData(['node-tags'], {
        object: 'node',
        attributes: {
            id: 4,
            relationships: {
                tags: { object: 'list', data: [minecraft] },
                deployment_tags: { object: 'list', data: [premium] },
            },
        },
    });

    render(
        <QueryClientProvider client={queryClient}>
            <NodeTagsTab />
        </QueryClientProvider>
    );

    return userEvent.setup();
};

afterEach(cleanup);
beforeEach(() => {
    mocks.calls = [];
});

describe('NodeTagsTab', () => {
    it('frees a reserved tag before saving it as a game', async () => {
        const user = setup();

        await user.deselectOptions(screen.getByLabelText('Reservations'), 'Premium (premium)');
        await user.selectOptions(screen.getByLabelText('Games'), 'Premium (premium)');
        await user.click(screen.getByRole('button', { name: 'Save tags' }));

        await waitFor(() => expect(mocks.calls).toEqual(['games:1', 'reservations:', 'games:1,2']));
    });

    it('does not offer a tag that the other list holds', () => {
        setup();

        expect(screen.getByLabelText('Games')).not.toHaveTextContent('Premium');
        expect(screen.getByLabelText('Reservations')).not.toHaveTextContent('Minecraft');
    });
});
