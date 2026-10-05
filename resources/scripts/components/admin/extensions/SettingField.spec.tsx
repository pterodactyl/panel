/** @vitest-environment jsdom */

import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { AdminExtensionSettingField } from '@/api/admin/extensions/queries';
import SettingField, { pickerColor, submittedItems } from './SettingField';

afterEach(cleanup);

const field = (overrides: Partial<AdminExtensionSettingField>): AdminExtensionSettingField => ({
    input: 'probe',
    label: 'Probe',
    help: null,
    tab: null,
    field: 'text',
    options: [],
    value: null,
    constraints: { max_length: null, max_items: null, max_kilobytes: null, accept: [] },
    visibility: 'admin',
    ...overrides,
});

describe('color field', () => {
    it('edits the colour as text and through the picker', () => {
        const onChange = vi.fn();
        render(<SettingField field={field({ field: 'color' })} value={'#1F2933'} onChange={onChange} />);

        expect(screen.getByLabelText('Probe picker')).toHaveValue('#1f2933');
        fireEvent.change(screen.getByLabelText('Probe picker'), { target: { value: '#abcdef' } });
        expect(onChange).toHaveBeenLastCalledWith('#abcdef');

        fireEvent.change(screen.getByRole('textbox', { name: 'Probe' }), {
            target: { value: 'oklch(0.62 0.19 259.8)' },
        });
        expect(onChange).toHaveBeenLastCalledWith('oklch(0.62 0.19 259.8)');
    });

    it('maps hex colours to the six digit form the picker needs', () => {
        expect(pickerColor('#abc')).toBe('#aabbcc');
        expect(pickerColor('#ABCD')).toBe('#aabbcc');
        expect(pickerColor('#11223344')).toBe('#112233');
        expect(pickerColor('oklch(0.62 0.19 259.8)')).toBeNull();
        expect(pickerColor('red')).toBeNull();
    });
});

describe('textarea field', () => {
    it('limits and counts the text', () => {
        const onChange = vi.fn();
        render(
            <SettingField
                field={field({
                    field: 'textarea',
                    constraints: { max_length: 20, max_items: null, max_kilobytes: null, accept: [] },
                })}
                value={'hello'}
                onChange={onChange}
            />
        );

        const input = screen.getByRole('textbox', { name: 'Probe' });
        expect(input).toHaveAttribute('maxlength', '20');
        expect(screen.getByText('5 / 20')).toBeInTheDocument();
        fireEvent.change(input, { target: { value: 'one\ntwo' } });
        expect(onChange).toHaveBeenLastCalledWith('one\ntwo');
    });
});

describe('list field', () => {
    const list = field({
        field: 'list',
        constraints: { max_length: null, max_items: 3, max_kilobytes: null, accept: [] },
    });

    it('edits, reorders, removes and adds items', () => {
        const onChange = vi.fn();
        render(<SettingField field={list} value={['a', 'b']} onChange={onChange} />);

        fireEvent.change(screen.getByRole('textbox', { name: 'Probe item 2' }), { target: { value: 'c' } });
        expect(onChange).toHaveBeenLastCalledWith(['a', 'c']);
        fireEvent.click(screen.getByRole('button', { name: 'Move item 2 up' }));
        expect(onChange).toHaveBeenLastCalledWith(['b', 'a']);
        fireEvent.click(screen.getByRole('button', { name: 'Remove item 1' }));
        expect(onChange).toHaveBeenLastCalledWith(['b']);
        fireEvent.click(screen.getByRole('button', { name: 'Add item' }));
        expect(onChange).toHaveBeenLastCalledWith(['a', 'b', '']);
        expect(screen.getByRole('button', { name: 'Move item 1 up' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Move item 2 down' })).toBeDisabled();
    });

    it('stops adding at the maximum and submits without blank rows', () => {
        render(<SettingField field={list} value={['a', 'b', 'c']} onChange={vi.fn()} />);

        expect(screen.getByRole('button', { name: 'Add item' })).toBeDisabled();
        expect(submittedItems([' a ', '', '  ', 'b'])).toEqual(['a', 'b']);
        expect(submittedItems('a | b')).toEqual([]);
    });
});

describe('file field', () => {
    const file = field({
        field: 'file',
        value: '/extension-files/probe/0123456789abcdef0123456789abcdef01234567.png',
        constraints: { max_length: null, max_items: null, max_kilobytes: 64, accept: ['image/png', 'image/webp'] },
        visibility: 'public',
    });

    it('uploads the chosen file and removes the stored one', () => {
        const actions = { pending: false, onUpload: vi.fn(), onClear: vi.fn() };
        const { container } = render(
            <SettingField field={file} value={file.value} onChange={vi.fn()} file={actions} />
        );

        const input = screen.getByLabelText('Probe');
        expect(input).toHaveAttribute('accept', 'image/png,image/webp');
        expect(container.querySelector('img')).toHaveAttribute('src', file.value);
        expect(screen.getByText('Visible to guests')).toBeInTheDocument();
        expect(screen.getByText(/image\/png, image\/webp · up to 64 KB/)).toBeInTheDocument();

        const upload = new File(['png'], 'logo.png', { type: 'image/png' });
        fireEvent.change(input, { target: { files: [upload] } });
        expect(actions.onUpload).toHaveBeenCalledWith(upload);

        fireEvent.click(screen.getByRole('button', { name: 'Remove' }));
        expect(actions.onClear).toHaveBeenCalledOnce();
    });

    it('offers no removal for a default that was not uploaded', () => {
        render(
            <SettingField
                field={{ ...file, value: '/favicons/favicon.ico' }}
                value={'/favicons/favicon.ico'}
                onChange={vi.fn()}
                file={{ pending: false, onUpload: vi.fn(), onClear: vi.fn() }}
            />
        );

        expect(screen.queryByRole('button', { name: 'Remove' })).toBeNull();
    });

    it('is disabled while an upload is pending', () => {
        render(
            <SettingField
                field={file}
                value={file.value}
                onChange={vi.fn()}
                file={{ pending: true, onUpload: vi.fn(), onClear: vi.fn() }}
            />
        );

        expect(screen.getByLabelText('Probe')).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Remove' })).toBeDisabled();
    });
});
