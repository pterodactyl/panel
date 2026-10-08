/** @vitest-environment jsdom */

import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import SpinnerOverlay from './SpinnerOverlay';
import { ServerError } from './ScreenBlock';

afterEach(cleanup);

describe('ServerError', () => {
    it('renders the error in place with a retry action', () => {
        const onRetry = vi.fn();

        render(<ServerError message='Request failed with status 500.' onRetry={onRetry} />);

        expect(screen.getByRole('heading', { name: 'Something went wrong' })).toBeInTheDocument();
        expect(screen.getByText('Request failed with status 500.')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Retry' }));

        expect(onRetry).toHaveBeenCalledOnce();
    });

    it('renders a back action', () => {
        const onBack = vi.fn();

        render(<ServerError title='Oops!' message='Not readable.' onBack={onBack} />);

        expect(screen.getByRole('heading', { name: 'Oops!' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Go back' }));

        expect(onBack).toHaveBeenCalledOnce();
    });
});

describe('SpinnerOverlay', () => {
    it('expresses the backdrop opacity as a percentage', () => {
        const { container } = render(<SpinnerOverlay visible backgroundOpacity={0.8} />);
        const overlay = container.querySelector<HTMLElement>('div')!;

        expect(overlay.style.getPropertyValue('--spinner-overlay-opacity')).toBe('80%');
    });
});
