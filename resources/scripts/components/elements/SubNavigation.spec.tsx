/** @vitest-environment jsdom */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import SubNavigation from './SubNavigation';

interface Layout {
    rowWidth: number;
    moreWidth: number;
    direction: 'row' | 'column';
}

interface Item {
    label: string;
    width: number;
    core?: boolean;
    active?: boolean;
}

let layout: Layout;

const rect = (left: number, width: number) => DOMRect.fromRect({ x: left, y: 0, width, height: 40 });

/** Lays the row's items out left to right at their `data-width`, starting at x = 0 with no padding. */
function fakeRect(this: HTMLElement): DOMRect {
    const parent = this.parentElement;

    if (parent?.dataset.testid === 'nav') {
        return rect(0, layout.rowWidth);
    }

    if (this.dataset.width === undefined) {
        return rect(0, layout.moreWidth);
    }

    let left = 0;

    for (let sibling = this.previousElementSibling; sibling instanceof HTMLElement;) {
        left += Number(sibling.dataset.width ?? 0);
        sibling = sibling.previousElementSibling;
    }

    return rect(left, Number(this.dataset.width));
}

const renderNavigation = (items: Item[]) =>
    render(
        <SubNavigation data-testid='nav'>
            {items.map((item) => (
                <a
                    key={item.label}
                    href={`#${item.label}`}
                    data-width={item.width}
                    data-core={item.core ? '' : undefined}
                    data-status={item.active ? 'active' : undefined}
                >
                    {item.label}
                </a>
            ))}
        </SubNavigation>
    );

const overflowed = () =>
    screen
        .getAllByRole('link')
        .filter((link) => link.hasAttribute('data-overflowed'))
        .map((link) => link.textContent);

const moreToggle = () => screen.getByText('More').closest('span')!;

describe('SubNavigation overflow', () => {
    beforeEach(() => {
        layout = { rowWidth: 400, moreWidth: 50, direction: 'row' };
        vi.stubGlobal(
            'ResizeObserver',
            class {
                observe() {}
                disconnect() {}
            }
        );
        vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockImplementation(fakeRect);

        const computedStyle = window.getComputedStyle.bind(window);

        vi.spyOn(window, 'getComputedStyle').mockImplementation((element) => {
            const style = computedStyle(element);

            style.paddingLeft = '0px';
            style.paddingRight = '0px';
            style.marginLeft = '0px';
            style.flexDirection = layout.direction;

            return style;
        });
    });

    afterEach(() => {
        cleanup();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('shows every item when they fit', () => {
        renderNavigation([
            { label: 'Console', width: 100 },
            { label: 'Files', width: 100 },
            { label: 'Backups', width: 100 },
        ]);

        expect(overflowed()).toEqual([]);
        expect(screen.getByTestId('nav')).not.toHaveAttribute('data-collapsed');
        expect(moreToggle().hidden).toBe(true);
    });

    it('keeps core and active items, then fills the rest in order', () => {
        renderNavigation([
            { label: 'Console', width: 100, core: true },
            { label: 'Files', width: 100 },
            { label: 'Backups', width: 100 },
            { label: 'Startup', width: 100, active: true },
            { label: 'Settings', width: 40 },
        ]);

        // Budget is 400 - 50 = 350: Console + Startup + Files fill 300, Backups would exceed it,
        // and filling stops there even though Settings alone would still fit.
        expect(overflowed()).toEqual(['Backups', 'Settings']);
        expect(screen.getByTestId('nav')).not.toHaveAttribute('data-collapsed');
        expect(moreToggle().hidden).toBe(false);
    });

    it('collapses when the core items alone exceed the budget', () => {
        renderNavigation([
            { label: 'Console', width: 200, core: true },
            { label: 'Files', width: 200, core: true },
            { label: 'Backups', width: 100 },
        ]);

        expect(screen.getByTestId('nav')).toHaveAttribute('data-collapsed');
        expect(overflowed()).toEqual([]);
        expect(moreToggle().hidden).toBe(true);
        expect(screen.getByRole('button', { name: /Navigation/ })).toHaveAttribute('aria-expanded', 'false');
    });

    it('names the collapsed toggle after the active item', () => {
        renderNavigation([
            { label: 'Console', width: 300, core: true },
            { label: 'Files', width: 150, core: true, active: true },
        ]);

        expect(screen.getByTestId('nav')).toHaveAttribute('data-collapsed');
        expect(screen.getByRole('button', { name: /^Files$/ })).toHaveAttribute('aria-expanded', 'false');
    });

    it('never overflows a column layout', () => {
        layout.direction = 'column';
        renderNavigation([
            { label: 'Console', width: 300, core: true },
            { label: 'Files', width: 300 },
            { label: 'Backups', width: 300 },
        ]);

        expect(overflowed()).toEqual([]);
        expect(screen.getByTestId('nav')).not.toHaveAttribute('data-collapsed');
        expect(moreToggle().hidden).toBe(true);
    });
});
