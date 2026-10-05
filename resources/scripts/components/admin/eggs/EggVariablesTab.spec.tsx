/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as EggQueries from '@/api/admin/eggs/queries';
import EggVariablesTab from './EggVariablesTab';

const variable = (id: number, name: string) => ({
    object: 'egg_variable',
    attributes: {
        id,
        name,
        description: '',
        env_variable: name.toUpperCase(),
        default_value: '',
        user_viewable: true,
        user_editable: true,
        rules: 'required|string',
    },
});

interface VariablesQuery {
    data?: { data: ReturnType<typeof variable>[] };
    error: null;
    isFetching: boolean;
    refetch: () => void;
}

let query: VariablesQuery;

vi.mock('@/components/admin/eggs/useEggDetail', () => ({ useEggDetail: () => ({ attributes: { id: 5 } }) }));
vi.mock('@/api/admin/eggs/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof EggQueries>()),
    useAdminEggVariables: () => query,
    useUpdateAdminEggVariable: () => ({ mutateAsync: vi.fn() }),
    useDeleteAdminEggVariable: () => ({ mutateAsync: vi.fn() }),
}));

afterEach(cleanup);
beforeEach(() => {
    query = { data: { data: [variable(1, 'version')] }, error: null, isFetching: false, refetch: vi.fn() };
});

describe('EggVariablesTab', () => {
    it('shows a spinner only until the variables first load', () => {
        query = { ...query, data: undefined, isFetching: true };
        const { container } = render(<EggVariablesTab />);

        expect(screen.queryByLabelText('Name')).not.toBeInTheDocument();
        expect(container.querySelector('[aria-busy]')).toBeNull();
    });

    it('keeps unsaved variable edits mounted through a background refetch', async () => {
        const user = userEvent.setup();
        const { rerender, container } = render(<EggVariablesTab />);

        const name = screen.getByLabelText('Name');
        await user.clear(name);
        await user.type(name, 'Server Version');

        query = { ...query, isFetching: true };
        rerender(<EggVariablesTab />);

        expect(screen.getByLabelText('Name')).toBe(name);
        expect(name).toHaveValue('Server Version');
        expect(container.querySelector('[aria-busy="true"]')).toContainElement(name);
    });
});
