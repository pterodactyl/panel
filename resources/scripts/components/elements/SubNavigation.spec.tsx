/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createExtensionTestHost, type ExtensionTestHost } from '@/sdk/testing';
import { navItemDestination, type NavItem } from '@/views/navigation';
import SubNavigation from './SubNavigation';

interface Layout {
    rowWidth: number;
    moreWidth: number;
    direction: 'row' | 'column';
}

interface Item {
    label: string;
    width: number;
    /** Extension items may move into the menu; the panel's own stay. */
    extension?: boolean;
}

let layout: Layout;
let widths: Map<string, number>;
let host: ExtensionTestHost | undefined;

const rect = (left: number, width: number) => DOMRect.fromRect({ x: left, y: 0, width, height: 40 });
const widthOf = (element: Element) =>
    element instanceof HTMLElement ? (widths.get(element.dataset.navItem ?? element.textContent ?? '') ?? 0) : 0;

/** Lays the row's children out left to right at their widths, starting at x = 0 with no padding. */
function fakeRect(this: HTMLElement): DOMRect {
    if (this.parentElement?.dataset.testid === 'nav') {
        return rect(0, layout.rowWidth);
    }

    if (this.parentElement?.parentElement?.dataset.testid !== 'nav' || this.querySelector('[aria-haspopup]')) {
        return rect(0, layout.moreWidth);
    }

    let left = 0;

    for (let sibling = this.previousElementSibling; sibling; sibling = sibling.previousElementSibling) {
        left += widthOf(sibling);
    }

    return rect(left, widthOf(this));
}

const navItem = ({ label, extension }: Item, order: number): NavItem => ({
    id: label.toLowerCase(),
    label,
    destination: navItemDestination(`/account/${label.toLowerCase()}`, {}),
    icon: null,
    group: null,
    order,
    exact: false,
    extensionId: extension ? 'tabs' : undefined,
});

async function renderNavigation(
    items: Item[],
    options: { path?: string; before?: ReactNode; widths?: Record<string, number> } = {}
) {
    const navItems = items.map(navItem);

    widths = new Map([
        ...items.map(
            (item, index) => [`${navItems[index].extensionId ?? ''}:${navItems[index].id}`, item.width] as const
        ),
        ...Object.entries(options.widths ?? {}),
    ]);
    host = createExtensionTestHost({ path: options.path ?? '/account' });

    await act(async () => {
        render(<SubNavigation data-testid='nav' items={navItems} before={options.before} />, {
            wrapper: host!.Wrapper,
        });
    });
    await screen.findByTestId('nav');
}

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
        host?.dispose();
        host = undefined;
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('shows every item when they fit', async () => {
        await renderNavigation([
            { label: 'Console', width: 100 },
            { label: 'Files', width: 100 },
            { label: 'Backups', width: 100 },
        ]);

        expect(overflowed()).toEqual([]);
        expect(screen.getByTestId('nav')).not.toHaveAttribute('data-collapsed');
        expect(moreToggle().hidden).toBe(true);
    });

    it('keeps the panel items and the active item, fills the rest in order, and lists the others in the menu', async () => {
        await renderNavigation(
            [
                { label: 'Console', width: 100 },
                { label: 'Files', width: 100, extension: true },
                { label: 'Backups', width: 100, extension: true },
                { label: 'Startup', width: 100, extension: true },
                { label: 'Settings', width: 40, extension: true },
            ],
            { path: '/account/startup' }
        );

        // Budget is 400 - 50 = 350: Console + Startup + Files fill 300, Backups would exceed it,
        // and filling stops there even though Settings alone would still fit.
        expect(overflowed()).toEqual(['Backups', 'Settings']);
        expect(screen.getByTestId('nav')).not.toHaveAttribute('data-collapsed');
        expect(moreToggle().hidden).toBe(false);

        fireEvent.click(screen.getByRole('button', { name: /More/ }));

        expect((await screen.findAllByRole('menuitem')).map((entry) => entry.textContent)).toEqual([
            'Backups',
            'Settings',
        ]);
    });

    it('highlights the menu holding the active item and navigates from it', async () => {
        await renderNavigation(
            [
                { label: 'Console', width: 300 },
                { label: 'Files', width: 100, extension: true },
                { label: 'Backups', width: 100, extension: true },
            ],
            { path: '/account/backups' }
        );

        expect(overflowed()).toEqual(['Files', 'Backups']);
        expect(screen.getByRole('button', { name: /More/ })).toHaveClass('shadow-navigation-active');

        fireEvent.click(screen.getByRole('button', { name: /More/ }));
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Files' }));

        await waitFor(() => expect(host!.location().pathname).toBe('/account/files'));
    });

    it('keeps slot content in the row', async () => {
        await renderNavigation(
            [
                { label: 'Files', width: 100, extension: true },
                { label: 'Backups', width: 100, extension: true },
            ],
            { before: <a href='#slot'>Slot link</a>, widths: { 'Slot link': 250 } }
        );

        // Budget is 350: the slot's 250 always stays, Files fits beside it and Backups does not.
        expect(overflowed()).toEqual(['Backups']);
        expect(screen.getByText('Slot link')).not.toHaveAttribute('data-overflowed');
    });

    it('collapses when the pinned items alone exceed the budget', async () => {
        await renderNavigation([
            { label: 'Console', width: 200 },
            { label: 'Files', width: 200 },
            { label: 'Backups', width: 100, extension: true },
        ]);

        expect(screen.getByTestId('nav')).toHaveAttribute('data-collapsed');
        expect(overflowed()).toEqual([]);
        expect(moreToggle().hidden).toBe(true);
        expect(screen.getByRole('button', { name: /Navigation/ })).toHaveAttribute('aria-expanded', 'false');
    });

    it('names the collapsed toggle after the active item', async () => {
        await renderNavigation(
            [
                { label: 'Console', width: 300 },
                { label: 'Files', width: 150 },
            ],
            { path: '/account/files' }
        );

        expect(screen.getByTestId('nav')).toHaveAttribute('data-collapsed');
        expect(screen.getByRole('button', { name: /^Files$/ })).toHaveAttribute('aria-expanded', 'false');
    });

    it('never overflows a column layout', async () => {
        layout.direction = 'column';
        await renderNavigation([
            { label: 'Console', width: 300 },
            { label: 'Files', width: 300, extension: true },
            { label: 'Backups', width: 300, extension: true },
        ]);

        expect(overflowed()).toEqual([]);
        expect(screen.getByTestId('nav')).not.toHaveAttribute('data-collapsed');
        expect(moreToggle().hidden).toBe(true);
    });
});
