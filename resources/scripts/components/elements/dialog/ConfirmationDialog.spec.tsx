/** @vitest-environment jsdom */

import type React from 'react';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Button from '@/components/elements/Button';
import { Dialog } from '.';

afterEach(cleanup);

type OnConfirmed = React.ComponentProps<typeof Dialog.ConfirmTrigger>['onConfirmed'];

const deferred = () => {
    let resolve!: () => void;
    let reject!: (reason: Error) => void;
    const promise = new Promise<void>((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return { promise, resolve, reject };
};

describe('Dialog.Confirm', () => {
    it('disables both actions and ignores close requests while pending', () => {
        const onClose = vi.fn();
        const onConfirmed = vi.fn();
        render(
            <Dialog.Confirm
                open
                pending
                title={'Delete'}
                confirm={'Delete'}
                onClose={onClose}
                onConfirmed={onConfirmed}
            >
                Are you sure?
            </Dialog.Confirm>
        );

        const confirm = screen.getByRole('button', { name: 'Delete' });
        expect(confirm).toBeDisabled();
        expect(confirm).toHaveAttribute('aria-busy', 'true');
        expect(screen.getByRole('button', { name: 'Cancel' })).toBeDisabled();

        fireEvent.click(confirm);
        fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });

        expect(onConfirmed).not.toHaveBeenCalled();
        expect(onClose).not.toHaveBeenCalled();
    });

    it('accepts one confirmation per click burst until the caller renders its pending state', () => {
        const onConfirmed = vi.fn();
        const dialog = (pending: boolean) => (
            <Dialog.Confirm
                open
                pending={pending}
                title={'Delete'}
                confirm={'Delete'}
                onClose={vi.fn()}
                onConfirmed={onConfirmed}
            />
        );
        const { rerender } = render(dialog(false));
        const confirm = screen.getByRole('button', { name: 'Delete' });

        act(() => {
            confirm.click();
            confirm.click();
        });
        expect(onConfirmed).toHaveBeenCalledOnce();

        rerender(dialog(true));
        rerender(dialog(false));
        fireEvent.click(confirm);
        expect(onConfirmed).toHaveBeenCalledTimes(2);
    });

    it('ignores confirmations once the dialog has been closed', () => {
        const onConfirmed = vi.fn();
        const { rerender } = render(
            <Dialog.Confirm open title={'Delete'} confirm={'Delete'} onClose={vi.fn()} onConfirmed={onConfirmed} />
        );
        const confirm = screen.getByRole('button', { name: 'Delete' });

        fireEvent.click(confirm);
        rerender(
            <Dialog.Confirm
                open={false}
                title={'Delete'}
                confirm={'Delete'}
                onClose={vi.fn()}
                onConfirmed={onConfirmed}
            />
        );
        fireEvent.click(confirm);

        expect(onConfirmed).toHaveBeenCalledOnce();
    });
});

describe('Dialog.ConfirmTrigger', () => {
    const renderTrigger = (onConfirmed: OnConfirmed) =>
        render(
            <Dialog.ConfirmTrigger
                title={'Reinstall'}
                confirm={'Reinstall'}
                trigger={({ onClick }) => <Button onClick={onClick}>Open</Button>}
                onConfirmed={onConfirmed}
            >
                Reinstall the server?
            </Dialog.ConfirmTrigger>
        );

    it('stays pending until the returned promise settles and fires only once', async () => {
        const request = deferred();
        const onConfirmed = vi.fn<OnConfirmed>((_event, close) => request.promise.then(close));
        renderTrigger(onConfirmed);

        fireEvent.click(screen.getByRole('button', { name: 'Open' }));
        const confirm = screen.getByRole('button', { name: 'Reinstall' });
        fireEvent.click(confirm);
        fireEvent.click(confirm);

        expect(onConfirmed).toHaveBeenCalledOnce();
        expect(confirm).toBeDisabled();

        await act(async () => request.resolve());

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('recovers from a rejected confirmation without an unhandled rejection', async () => {
        const request = deferred();
        const onConfirmed = vi.fn<OnConfirmed>(() => request.promise);
        renderTrigger(onConfirmed);

        fireEvent.click(screen.getByRole('button', { name: 'Open' }));
        const confirm = screen.getByRole('button', { name: 'Reinstall' });
        fireEvent.click(confirm);
        expect(confirm).toBeDisabled();

        await act(async () => request.reject(new Error('Request failed')));

        expect(confirm).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Cancel' })).toBeEnabled();
        fireEvent.click(confirm);
        expect(onConfirmed).toHaveBeenCalledTimes(2);
    });

    it('does not enter a pending state for synchronous handlers', () => {
        const onConfirmed = vi.fn<OnConfirmed>();
        renderTrigger(onConfirmed);

        fireEvent.click(screen.getByRole('button', { name: 'Open' }));
        fireEvent.click(screen.getByRole('button', { name: 'Reinstall' }));

        expect(onConfirmed).toHaveBeenCalledOnce();
        expect(screen.getByRole('button', { name: 'Reinstall' })).toBeEnabled();
    });
});
