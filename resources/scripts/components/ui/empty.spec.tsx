/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from './empty';

afterEach(cleanup);

describe('Empty', () => {
    it('renders each part with its slot and merges class names', () => {
        const { container } = render(
            <Empty className='border md:p-8' data-testid='empty'>
                <EmptyHeader>
                    <EmptyMedia variant='icon'>
                        <svg />
                    </EmptyMedia>
                    <EmptyTitle>No databases</EmptyTitle>
                    <EmptyDescription>
                        Create one from <a href='/'>the panel</a>.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <button type='button'>Create database</button>
                </EmptyContent>
            </Empty>
        );

        const root = screen.getByTestId('empty');

        expect(root).toHaveAttribute('data-slot', 'empty');
        expect(root).toHaveClass('border', 'md:p-8', 'p-6');
        expect(root).not.toHaveClass('md:p-12');
        expect(
            [...container.querySelectorAll('[data-slot]')].map((element) => element.getAttribute('data-slot'))
        ).toEqual(['empty', 'empty-header', 'empty-icon', 'empty-title', 'empty-description', 'empty-content']);
        expect(screen.getByText('No databases')).toHaveAttribute('data-slot', 'empty-title');
        expect(screen.getByRole('link', { name: 'the panel' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Create database' })).toBeInTheDocument();
    });

    it('frames icons only for the icon media variant', () => {
        render(
            <>
                <EmptyMedia data-testid='default' />
                <EmptyMedia data-testid='icon' variant='icon' className='size-12' />
            </>
        );

        expect(screen.getByTestId('default')).toHaveAttribute('data-variant', 'default');
        expect(screen.getByTestId('default')).toHaveClass('bg-transparent');
        expect(screen.getByTestId('icon')).toHaveAttribute('data-variant', 'icon');
        expect(screen.getByTestId('icon')).toHaveClass('bg-secondary', 'size-12');
        expect(screen.getByTestId('icon')).not.toHaveClass('size-10');
    });

    it('lets callers override the slot attribute', () => {
        render(<EmptyTitle data-slot='custom-title'>Title</EmptyTitle>);

        expect(screen.getByText('Title')).toHaveAttribute('data-slot', 'custom-title');
    });
});
