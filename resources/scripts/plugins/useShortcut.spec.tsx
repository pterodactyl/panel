/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import { useShortcut } from './useShortcut';

function Harness({ keys, onKey, when }: { keys: string; onKey: () => void; when?: boolean }) {
    const shortcut = useShortcut(keys, onKey, { when });

    return (
        <>
            <button type='button' aria-keyshortcuts={shortcut.aria}>
                {shortcut.label}
            </button>
            <input aria-label='Name' />
            <div role='dialog'>
                <button type='button'>Confirm</button>
            </div>
        </>
    );
}

afterEach(cleanup);

it('calls the handler for its key, and names it', () => {
    const onKey = vi.fn();

    render(<Harness keys='g' onKey={onKey} />);
    fireEvent.keyDown(document.body, { key: 'g' });
    fireEvent.keyDown(document.body, { key: 'g', ctrlKey: true });
    fireEvent.keyDown(document.body, { key: 'h' });

    expect(onKey).toHaveBeenCalledOnce();
    expect(screen.getByRole('button', { name: 'G' })).toHaveAttribute('aria-keyshortcuts', 'G');
});

it('reads mod as Ctrl off Apple devices', () => {
    const onKey = vi.fn();

    render(<Harness keys='mod+/' onKey={onKey} />);
    fireEvent.keyDown(document.body, { key: '/' });
    fireEvent.keyDown(document.body, { key: '/', metaKey: true });
    fireEvent.keyDown(document.body, { key: '/', ctrlKey: true });

    expect(onKey).toHaveBeenCalledOnce();
    expect(screen.getByRole('button', { name: 'Ctrl+/' })).toHaveAttribute('aria-keyshortcuts', 'Control+/');
});

it('leaves keys to fields, dialogs and earlier handlers, and stays off unless `when`', () => {
    const onKey = vi.fn();
    const { rerender } = render(<Harness keys='g' onKey={onKey} />);

    fireEvent.keyDown(screen.getByRole('textbox'), { key: 'g' });
    fireEvent.keyDown(screen.getByRole('button', { name: 'Confirm' }), { key: 'g' });

    const taken = new KeyboardEvent('keydown', { key: 'g', bubbles: true, cancelable: true });

    taken.preventDefault();
    document.body.dispatchEvent(taken);

    rerender(<Harness keys='g' onKey={onKey} when={false} />);
    fireEvent.keyDown(document.body, { key: 'g' });

    expect(onKey).not.toHaveBeenCalled();
});
