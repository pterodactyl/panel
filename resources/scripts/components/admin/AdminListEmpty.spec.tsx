/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Server } from 'lucide-react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import AdminListEmpty from './AdminListEmpty';

afterEach(cleanup);

describe('AdminListEmpty', () => {
    it('offers the create action when nothing exists yet', () => {
        render(
            <AdminListEmpty
                icon={Server}
                noun={'servers'}
                onClearFilter={vi.fn()}
                description={'Create a server to get started.'}
                action={<button type={'button'}>New server</button>}
            />
        );

        expect(screen.getByText('No servers yet')).toBeInTheDocument();
        expect(screen.getByText('Create a server to get started.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'New server' })).toBeInTheDocument();
    });

    it('explains a search miss and clears the search instead of offering creation', async () => {
        const onClearFilter = vi.fn();
        render(
            <AdminListEmpty
                icon={Server}
                noun={'servers'}
                filter={'minecraft'}
                onClearFilter={onClearFilter}
                description={'Create a server to get started.'}
                action={<button type={'button'}>New server</button>}
            />
        );

        expect(screen.getByText('No matching servers')).toBeInTheDocument();
        expect(screen.getByText('minecraft')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'New server' })).not.toBeInTheDocument();

        await userEvent.setup().click(screen.getByRole('button', { name: 'Clear search' }));

        expect(onClearFilter).toHaveBeenCalledOnce();
    });
});
