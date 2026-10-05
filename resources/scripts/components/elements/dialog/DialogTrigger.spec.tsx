/** @vitest-environment jsdom */

import { useState } from 'react';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DialogTrigger from './DialogTrigger';

const DraftDialog = ({ open, onClose }: { open: boolean; onClose: () => void }) => {
    const [draft, setDraft] = useState('');

    return (
        <div data-testid={'dialog'} data-open={open}>
            <input aria-label={'Draft'} value={draft} onChange={(event) => setDraft(event.currentTarget.value)} />
            <button type={'button'} onClick={onClose}>
                Cancel
            </button>
        </div>
    );
};

const renderTrigger = (children = (props: { open: boolean; onClose: () => void }) => <DraftDialog {...props} />) =>
    render(
        <DialogTrigger
            trigger={({ onClick }) => (
                <button type={'button'} onClick={onClick}>
                    Open
                </button>
            )}
        >
            {children}
        </DialogTrigger>
    );

afterEach(cleanup);

describe('DialogTrigger', () => {
    it('does not render the dialog until it is first opened', async () => {
        const user = userEvent.setup();
        const children = vi.fn((props: { open: boolean; onClose: () => void }) => <DraftDialog {...props} />);
        renderTrigger(children);

        expect(children).not.toHaveBeenCalled();
        expect(screen.queryByTestId('dialog')).toBeNull();

        await user.click(screen.getByRole('button', { name: 'Open' }));

        expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');
        expect(children).toHaveBeenCalledWith(expect.objectContaining({ open: false }));
        expect(children).toHaveBeenLastCalledWith(expect.objectContaining({ open: true }));
    });

    it('keeps the closed dialog mounted until the next open', async () => {
        const user = userEvent.setup();
        renderTrigger();

        await user.click(screen.getByRole('button', { name: 'Open' }));
        await user.type(screen.getByLabelText('Draft'), 'secret');
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');
        expect(screen.getByLabelText('Draft')).toHaveValue('secret');
    });

    it('gives every open a fresh dialog state', async () => {
        const user = userEvent.setup();
        renderTrigger();

        await user.click(screen.getByRole('button', { name: 'Open' }));
        await user.type(screen.getByLabelText('Draft'), 'secret');
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(screen.getByRole('button', { name: 'Open' }));

        expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');
        expect(screen.getByLabelText('Draft')).toHaveValue('');
    });
});
