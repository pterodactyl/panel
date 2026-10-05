/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Select from './Select';

afterEach(cleanup);

const alpha = { value: 1, label: 'Alpha' };
const beta = { value: 2, label: 'Beta' };
const gamma = { value: 3, label: 'Gamma' };

describe('Select', () => {
    it('keeps the selected label when the option leaves a server-side search result', () => {
        const props = { value: 1, onChange: vi.fn(), onSearchChange: vi.fn() };
        const { rerender } = render(<Select {...props} options={[alpha, beta]} />);
        const input = screen.getByRole('combobox');

        expect(input).toHaveValue('Alpha');

        rerender(<Select {...props} options={[gamma]} />);

        expect(input).toHaveValue('Alpha');
    });

    it('drops the remembered label once the value changes', () => {
        const onChange = vi.fn();
        const { rerender } = render(<Select value={1} onChange={onChange} options={[alpha]} />);
        rerender(<Select value={2} onChange={onChange} options={[gamma]} />);

        expect(screen.getByRole('combobox')).toHaveValue('');
    });

    it('keeps every selected option of a multi-select when they leave the option list', () => {
        const props = { multiple: true as const, value: [1, 2], onChange: vi.fn() };
        const { rerender } = render(<Select {...props} options={[alpha, beta]} />);

        expect(screen.getByText('2 selected')).toBeInTheDocument();

        rerender(<Select {...props} options={[gamma]} />);

        expect(screen.getByText('2 selected')).toBeInTheDocument();
    });

    it('forwards the description and invalid state to the input', () => {
        render(
            <Select value={null} onChange={vi.fn()} options={[alpha]} aria-describedby={'select-help'} aria-invalid />
        );
        const input = screen.getByRole('combobox');

        expect(input).toHaveAttribute('aria-describedby', 'select-help');
        expect(input).toHaveAttribute('aria-invalid', 'true');
    });
});
