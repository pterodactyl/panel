/** @vitest-environment jsdom */

import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { TextInput } from '@/components/form/controls';
import CopyOnClick from './CopyOnClick';

const clipboard = vi.hoisted(() => ({ copy: vi.fn<(text: string) => Promise<boolean>>() }));

vi.mock('@/lib/clipboard', () => ({ default: clipboard.copy }));

afterEach(() => {
    cleanup();
    document.getElementById('modal-portal')?.remove();
});
beforeEach(() => {
    const portal = document.createElement('div');

    portal.id = 'modal-portal';
    document.body.append(portal);
    clipboard.copy.mockReset();
    clipboard.copy.mockResolvedValue(true);
});

describe('CopyOnClick', () => {
    it('does not echo the copied value by default', async () => {
        render(
            <CopyOnClick text='ptlc_secret'>
                <code>ptlc_secret</code>
            </CopyOnClick>
        );

        await act(async () => fireEvent.click(screen.getByRole('button', { name: 'ptlc_secret' })));

        expect(clipboard.copy).toHaveBeenCalledWith('ptlc_secret');
        expect(screen.getByRole('status')).toHaveTextContent('Copied text to clipboard.');
    });

    it('echoes the value only when asked to', async () => {
        render(
            <CopyOnClick text='node-1' showInNotification>
                <code>node-1</code>
            </CopyOnClick>
        );

        await act(async () => fireEvent.click(screen.getByRole('button', { name: 'node-1' })));

        expect(screen.getByRole('status')).toHaveTextContent('Copied "node-1" to clipboard.');
    });

    it('reports a failed copy', async () => {
        clipboard.copy.mockResolvedValue(false);
        render(
            <CopyOnClick text='value'>
                <span>value</span>
            </CopyOnClick>
        );

        await act(async () => fireEvent.click(screen.getByRole('button', { name: 'value' })));

        expect(screen.getByRole('status')).toHaveTextContent('Unable to copy text to clipboard.');
    });

    it('makes non-interactive children focusable and copies on Enter and Space', async () => {
        render(
            <CopyOnClick text='uuid'>
                <code>uuid</code>
            </CopyOnClick>
        );
        const target = screen.getByRole('button', { name: 'uuid' });

        expect(target).toHaveAttribute('tabindex', '0');

        await act(async () => fireEvent.keyDown(target, { key: 'Enter' }));
        await act(async () => fireEvent.keyDown(target, { key: ' ' }));

        expect(clipboard.copy).toHaveBeenCalledTimes(2);
    });

    it('leaves interactive children with their native role', async () => {
        render(
            <CopyOnClick text='sftp://example'>
                <TextInput aria-label='SFTP address' readOnly value='sftp://example' />
            </CopyOnClick>
        );
        const input = screen.getByRole('textbox', { name: 'SFTP address' });

        expect(input).not.toHaveAttribute('role');
        await act(async () => fireEvent.click(input));

        expect(clipboard.copy).toHaveBeenCalledWith('sftp://example');
    });
});
