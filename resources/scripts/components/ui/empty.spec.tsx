/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from './empty';

afterEach(cleanup);

describe('Empty', () => {
    it('renders each part with its skin attributes and merges class names', () => {
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

        expect(root).toHaveAttribute('data-ui', 'empty');
        expect(root).toHaveAttribute('data-size', 'default');
        expect(root).toHaveClass('border', 'md:p-8', 'p-6');
        expect(root).not.toHaveClass('md:p-12');
        expect(
            [...container.querySelectorAll('[data-ui="empty"]')].map((element) => element.getAttribute('data-part'))
        ).toEqual(['root', 'header', 'media', 'title', 'description', 'content']);
        expect(screen.getByText('No databases')).toHaveAttribute('data-part', 'title');
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

    it('tightens a compact empty state', () => {
        render(<Empty size='compact' data-testid='empty' />);

        expect(screen.getByTestId('empty')).toHaveClass('gap-4', 'md:p-8');
        expect(screen.getByTestId('empty')).not.toHaveClass('gap-6', 'md:p-12');
        expect(screen.getByTestId('empty')).toHaveAttribute('data-size', 'compact');
    });
});
