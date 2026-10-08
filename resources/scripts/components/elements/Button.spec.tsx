/** @vitest-environment jsdom */

import type { FormEvent } from 'react';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Button from './Button';

afterEach(cleanup);

describe('Button', () => {
    it('defaults to a non-submitting button', () => {
        const onSubmit = vi.fn((event: FormEvent) => event.preventDefault());

        render(
            <form onSubmit={onSubmit}>
                <Button.Text onClick={vi.fn()}>Cancel</Button.Text>
                <Button type='submit'>Save</Button>
            </form>
        );

        expect(screen.getByRole('button', { name: 'Cancel' })).toHaveAttribute('type', 'button');
        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(onSubmit).not.toHaveBeenCalled();

        fireEvent.click(screen.getByRole('button', { name: 'Save' }));
        expect(onSubmit).toHaveBeenCalledOnce();
    });

    it('disables itself and reports busy while loading', () => {
        const onClick = vi.fn();

        render(
            <Button isLoading onClick={onClick}>
                Rotate
            </Button>
        );

        const button = screen.getByRole('button', { name: 'Rotate' });

        expect(button).toBeDisabled();
        expect(button).toHaveAttribute('aria-busy', 'true');
        expect(button.querySelector('div')).toBeNull();

        fireEvent.click(button);
        expect(onClick).not.toHaveBeenCalled();
    });

    it('stays enabled without a busy state when idle', () => {
        render(<Button>Idle</Button>);

        const button = screen.getByRole('button', { name: 'Idle' });

        expect(button).toBeEnabled();
        expect(button).not.toHaveAttribute('aria-busy');
    });
});
