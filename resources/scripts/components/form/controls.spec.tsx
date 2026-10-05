/** @vitest-environment jsdom */

import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { NumberInput } from './controls';

afterEach(cleanup);

describe('NumberInput', () => {
    it('renders on its own and reports the entered number', () => {
        const onValueChange = vi.fn();
        render(<NumberInput aria-label={'Limit'} value={10} onValueChange={onValueChange} />);

        const input = screen.getByRole('textbox', { name: 'Limit' });
        expect(input).toHaveValue('10');

        fireEvent.change(input, { target: { value: '25' } });
        fireEvent.blur(input);

        expect(onValueChange).toHaveBeenLastCalledWith(25, expect.anything());
    });

    it('reports an emptied field as null', () => {
        const onValueChange = vi.fn();
        render(<NumberInput aria-label={'Limit'} value={10} onValueChange={onValueChange} />);

        const input = screen.getByRole('textbox', { name: 'Limit' });
        fireEvent.change(input, { target: { value: '' } });
        fireEvent.blur(input);

        expect(onValueChange).toHaveBeenLastCalledWith(null, expect.anything());
    });
});
