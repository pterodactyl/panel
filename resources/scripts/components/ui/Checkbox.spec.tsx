/** @vitest-environment jsdom */

import { createRef } from 'react';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Checkbox from './Checkbox';

afterEach(cleanup);

describe('Checkbox', () => {
    it('exposes an accessible name, forwarded attributes and a ref', () => {
        const ref = createRef<HTMLElement>();
        render(
            <Checkbox ref={ref} aria-label={'Select all files'} data-row={'1'} checked={false} onChange={vi.fn()} />
        );

        const checkbox = screen.getByRole('checkbox', { name: 'Select all files' });
        expect(checkbox).toHaveAttribute('data-row', '1');
        expect(ref.current).toBe(checkbox);
    });

    it('reports a partial selection as mixed and selects all when toggled', () => {
        const onChange = vi.fn();
        render(<Checkbox aria-label={'Select all'} checked={false} indeterminate onChange={onChange} />);

        const checkbox = screen.getByRole('checkbox', { name: 'Select all' });
        expect(checkbox).toHaveAttribute('aria-checked', 'mixed');

        fireEvent.click(checkbox);
        expect(onChange).toHaveBeenCalledWith(true, expect.anything());
    });
});
