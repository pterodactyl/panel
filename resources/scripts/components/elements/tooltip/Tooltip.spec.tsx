/** @vitest-environment jsdom */

import type React from 'react';
import { useEffect } from 'react';
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Tooltip from './Tooltip';

afterEach(cleanup);

describe('Tooltip', () => {
    it('keeps the reference mounted when toggling disabled', () => {
        const mounted = vi.fn();
        const Target = (props: React.ComponentProps<'button'>) => {
            useEffect(() => mounted(), []);

            return (
                <button type='button' {...props}>
                    Target
                </button>
            );
        };

        const { rerender } = render(
            <Tooltip content='Help' disabled>
                <Target />
            </Tooltip>
        );

        rerender(
            <Tooltip content='Help'>
                <Target />
            </Tooltip>
        );
        rerender(
            <Tooltip content='Help' disabled>
                <Target />
            </Tooltip>
        );

        expect(mounted).toHaveBeenCalledOnce();
        expect(screen.getByRole('button', { name: 'Target' })).toBeInTheDocument();
    });

    it('attaches the child ref once across re-renders', () => {
        const ref = vi.fn();
        const { rerender } = render(
            <Tooltip content='First'>
                <button type='button' ref={ref}>
                    Target
                </button>
            </Tooltip>
        );

        rerender(
            <Tooltip content='Second'>
                <button type='button' ref={ref}>
                    Target
                </button>
            </Tooltip>
        );

        expect(ref).toHaveBeenCalledOnce();
        expect(ref).toHaveBeenCalledWith(screen.getByRole('button', { name: 'Target' }));
    });
});
