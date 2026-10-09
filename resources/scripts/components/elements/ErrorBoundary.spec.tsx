/** @vitest-environment jsdom */

import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ErrorBoundary from './ErrorBoundary';
import Spinner from './Spinner';

afterEach(cleanup);
beforeEach(() => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
});
afterEach(() => vi.restoreAllMocks());

const failure = { active: true };

const Flaky = () => {
    if (failure.active) {
        throw new Error('Chunk failed to load');
    }

    return <p>Loaded</p>;
};

describe('ErrorBoundary', () => {
    beforeEach(() => {
        failure.active = true;
    });

    it('renders its children again after Retry', () => {
        render(
            <ErrorBoundary>
                <Flaky />
            </ErrorBoundary>
        );
        expect(screen.getByRole('alert')).toBeInTheDocument();

        failure.active = false;
        fireEvent.click(screen.getByRole('button', { name: 'Retry' }));

        expect(screen.getByText('Loaded')).toBeInTheDocument();
    });

    it('resets when a reset key changes', () => {
        const { rerender } = render(
            <ErrorBoundary resetKeys={['/server/a/files']}>
                <Flaky />
            </ErrorBoundary>
        );

        expect(screen.getByRole('alert')).toBeInTheDocument();

        failure.active = false;
        rerender(
            <ErrorBoundary resetKeys={['/server/a/files']}>
                <Flaky />
            </ErrorBoundary>
        );
        expect(screen.getByRole('alert')).toBeInTheDocument();

        rerender(
            <ErrorBoundary resetKeys={['/server/a/databases']}>
                <Flaky />
            </ErrorBoundary>
        );
        expect(screen.getByText('Loaded')).toBeInTheDocument();
    });

    it('guards Spinner.Suspense content outside a router', () => {
        render(
            <Spinner.Suspense>
                <Flaky />
            </Spinner.Suspense>
        );
        expect(screen.getByRole('alert')).toBeInTheDocument();

        failure.active = false;
        fireEvent.click(screen.getByRole('button', { name: 'Retry' }));

        expect(screen.getByText('Loaded')).toBeInTheDocument();
    });
});
