import React, { useId, useLayoutEffect, useRef, useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { ChevronDown, Menu } from 'lucide-react';
import { cn } from '@/lib/cn';
import Icon from '@/components/elements/Icon';
import DropdownMenu from '@/components/elements/dropdown/DropdownMenu';
import NavigationLabel from '@/extensions/NavigationLabel';
import {
    Navigation,
    NavigationLink,
    navItemBadge,
    navItemKey,
    useActiveNavItem,
    useNavItemActive,
    type NavItem,
    type NavItemState,
} from '@/views/navigation';

const subNavigationClass = [
    'w-full bg-card shadow-sm overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden',
    '[&>div]:mx-auto [&>div]:flex [&>div]:max-w-panel [&>div]:items-center [&>div]:px-2 [&>div]:text-sm',
    '[&>div>a]:inline-block [&>div>a]:py-3 [&>div>a]:px-4 [&>div>a]:text-muted-foreground [&>div>a]:no-underline [&>div>a]:whitespace-nowrap [&>div>a]:transition-colors [&>div>a]:duration-150',
    '[&>div>div]:inline-block [&>div>div]:py-3 [&>div>div]:px-4 [&>div>div]:text-muted-foreground [&>div>div]:no-underline [&>div>div]:whitespace-nowrap [&>div>div]:transition-colors [&>div>div]:duration-150',
    '[&>div>a:not(:first-of-type)]:ml-2 [&>div>div:not(:first-of-type)]:ml-2',
    '[&>div>a:hover]:text-foreground [&>div>div:hover]:text-foreground',
    '[&>div>a:active]:text-foreground [&>div>a.active]:text-foreground [&>div>a[data-status=active]]:text-foreground',
    '[&>div>div:active]:text-foreground [&>div>div.active]:text-foreground [&>div>div[data-status=active]]:text-foreground',
    '[&>div>a:active]:shadow-navigation-active [&>div>a.active]:shadow-navigation-active [&>div>a[data-status=active]]:shadow-navigation-active',
    '[&>div>div:active]:shadow-navigation-active [&>div>div.active]:shadow-navigation-active [&>div>div[data-status=active]]:shadow-navigation-active',
    '[&>div>[data-overflowed]]:hidden',
].join(' ');

// Applies when `<html data-sub-navigation="side">` is set (lg and up).
const sideNavigationClass = [
    'side-navigation:sticky side-navigation:top-0 side-navigation:max-h-dvh side-navigation:w-sidebar side-navigation:shrink-0',
    'side-navigation:overflow-x-visible side-navigation:overflow-y-auto side-navigation:bg-transparent side-navigation:px-4 side-navigation:py-6 side-navigation:shadow-none',
    'side-navigation:[&>div]:flex-col side-navigation:[&>div]:items-stretch side-navigation:[&>div]:gap-0.5 side-navigation:[&>div]:px-0',
    'side-navigation:[&>div>a]:block side-navigation:[&>div>a]:rounded-md side-navigation:[&>div>a]:px-3 side-navigation:[&>div>a]:py-2',
    'side-navigation:[&>div>div]:block side-navigation:[&>div>div]:rounded-md side-navigation:[&>div>div]:px-3 side-navigation:[&>div>div]:py-2',
    'side-navigation:[&>div>a:not(:first-of-type)]:ml-0 side-navigation:[&>div>div:not(:first-of-type)]:ml-0',
    'side-navigation:[&>div>a:hover]:bg-card side-navigation:[&>div>div:hover]:bg-card',
    'side-navigation:[&>div>a:active]:bg-popover side-navigation:[&>div>a.active]:bg-popover side-navigation:[&>div>a[data-status=active]]:bg-popover',
    'side-navigation:[&>div>div:active]:bg-popover side-navigation:[&>div>div.active]:bg-popover side-navigation:[&>div>div[data-status=active]]:bg-popover',
    'side-navigation:[&>div>a:hover]:shadow-none side-navigation:[&>div>a:active]:shadow-none side-navigation:[&>div>a.active]:shadow-none side-navigation:[&>div>a[data-status=active]]:shadow-none',
    'side-navigation:[&>div>div:hover]:shadow-none side-navigation:[&>div>div:active]:shadow-none side-navigation:[&>div>div.active]:shadow-none side-navigation:[&>div>div[data-status=active]]:shadow-none',
].join(' ');

const collapsedNavigationClass = [
    '[&[data-collapsed]>button]:flex [&[data-collapsed]:not([data-open])>div]:hidden',
    '[&[data-collapsed]>div]:flex-col [&[data-collapsed]>div]:items-stretch [&[data-collapsed]>div]:gap-0.5 [&[data-collapsed]>div]:pb-2',
    '[&[data-collapsed]>div>a]:block! [&[data-collapsed]>div>a]:ml-0! [&[data-collapsed]>div>a]:rounded-md [&[data-collapsed]>div>a]:px-4 [&[data-collapsed]>div>a]:py-2 [&[data-collapsed]>div>a]:shadow-none!',
    '[&[data-collapsed]>div>div]:block! [&[data-collapsed]>div>div]:ml-0! [&[data-collapsed]>div>div]:rounded-md [&[data-collapsed]>div>div]:px-4 [&[data-collapsed]>div>div]:py-2 [&[data-collapsed]>div>div]:shadow-none!',
    '[&[data-collapsed]>div>a:hover]:bg-popover/60 [&[data-collapsed]>div>div:hover]:bg-popover/60',
    '[&[data-collapsed]>div>a[data-status=active]]:bg-popover [&[data-collapsed]>div>div[data-status=active]]:bg-popover',
].join(' ');

export interface SubNavigationItemState extends NavItemState {
    /** In the row, or in the "More" menu once the row ran out of room. */
    readonly placement: 'row' | 'menu';
}

export interface SubNavigationProps extends Omit<React.ComponentProps<'div'>, 'children'> {
    readonly items: readonly NavItem[];
    /** Rendered around the items, typically the area's navigation slots; it never moves into the menu. */
    readonly before?: React.ReactNode;
    readonly after?: React.ReactNode;
    /** The items that may move into the "More" menu; by default those of extensions. */
    readonly canOverflow?: (item: NavItem) => boolean;
    /** The content of an item's link and menu entry; by default its icon, label and badge. */
    readonly renderItem?: (item: NavItem, state: SubNavigationItemState) => React.ReactNode;
}

interface OverflowState {
    /** Keys of the items moved into the menu, in row order. */
    overflowed: string[];
    collapsed: boolean;
}

/** What the measurement needs from the model; read through a ref so observers see the latest. */
interface OverflowPolicy {
    movable: ReadonlySet<string>;
    active: string | undefined;
}

type OverflowLayout = {
    collapsed: boolean;
    overflowed: HTMLElement[];
};

const itemKeyOf = (element: HTMLElement) => element.dataset.navItem;

const defaultOverflow = (item: NavItem) => item.extensionId !== undefined;

const defaultRender = (item: NavItem, state: SubNavigationItemState) => (
    <NavigationLabel label={item.label} group={item.group} icon={item.icon} badge={state.badge} />
);

/** Each child's width including the gap before it, so the widths add up to the row's used space. */
const measureWidths = (row: HTMLElement, children: HTMLElement[], style: CSSStyleDeclaration) => {
    const rect = row.getBoundingClientRect();
    const available = rect.width - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
    let edge = rect.left + parseFloat(style.paddingLeft);
    const widths = children.map((child) => {
        const right = child.getBoundingClientRect().right;
        const width = Math.max(0, right - edge);

        edge = Math.max(edge, right);

        return width;
    });

    return { available, widths };
};

/**
 * Pinned children (core items and slot content) always stay; the active item claims the budget
 * next, then the rest in order until it runs out.
 */
const fitWithinBudget = (
    children: HTMLElement[],
    widths: number[],
    budget: number,
    policy: OverflowPolicy
): OverflowLayout => {
    const isMovable = (child: HTMLElement) => policy.movable.has(itemKeyOf(child) ?? '');
    const visible = new Set(children.filter((child) => !isMovable(child)));
    let used = children.reduce((sum, child, index) => sum + (visible.has(child) ? widths[index] : 0), 0);

    if (used > budget) {
        return { collapsed: true, overflowed: [] };
    }

    const active = children.findIndex((child) => !visible.has(child) && itemKeyOf(child) === policy.active);

    if (active !== -1 && used + widths[active] <= budget) {
        visible.add(children[active]);
        used += widths[active];
    }

    for (const [index, child] of children.entries()) {
        if (visible.has(child)) {
            continue;
        }

        if (used + widths[index] > budget) {
            break;
        }

        visible.add(child);
        used += widths[index];
    }

    return { collapsed: false, overflowed: children.filter((child) => !visible.has(child)) };
};

/** Shows the "more" toggle while measuring its width when the row overflows. */
const overflowLayout = (
    row: HTMLElement,
    children: HTMLElement[],
    more: HTMLElement,
    policy: OverflowPolicy
): OverflowLayout => {
    const style = getComputedStyle(row);

    if (style.flexDirection === 'column') {
        return { collapsed: false, overflowed: [] };
    }

    const { available, widths } = measureWidths(row, children, style);
    const total = widths.reduce((sum, width) => sum + width, 0);

    if (total <= available) {
        return { collapsed: false, overflowed: [] };
    }

    more.hidden = false;
    const budget = available - more.getBoundingClientRect().width - parseFloat(getComputedStyle(more).marginLeft);

    return fitWithinBudget(children, widths, budget, policy);
};

const sameState = (previous: OverflowState, next: OverflowState) =>
    previous.collapsed === next.collapsed && previous.overflowed.join('\n') === next.overflowed.join('\n');

/**
 * Measures the rendered row and marks what does not fit with `data-overflowed` (or the whole
 * navigation with `data-collapsed`). Gated items render nothing, so the row is what counts; the
 * menu is then built from the model.
 */
function useOverflowNavigation(
    rootRef: React.RefObject<HTMLDivElement | null>,
    rowRef: React.RefObject<HTMLDivElement | null>,
    moreRef: React.RefObject<HTMLSpanElement | null>,
    policy: OverflowPolicy
) {
    const [state, setState] = useState<OverflowState>({ overflowed: [], collapsed: false });
    const policyRef = useRef(policy);
    const updateRef = useRef<() => void>(undefined);

    policyRef.current = policy;

    useLayoutEffect(() => {
        const root = rootRef.current;
        const row = rowRef.current;
        const more = moreRef.current;

        if (!root || !row || !more) {
            return;
        }

        let mounted = true;

        const update = () => {
            const children = [...row.children].filter(
                (child): child is HTMLElement => child !== more && child instanceof HTMLElement
            );

            for (const child of children) {
                delete child.dataset.overflowed;
            }

            delete root.dataset.collapsed;
            more.hidden = true;

            const { collapsed, overflowed } = overflowLayout(row, children, more, policyRef.current);

            if (collapsed) {
                root.dataset.collapsed = '';
            }

            for (const child of overflowed) {
                child.dataset.overflowed = '';
            }

            more.hidden = overflowed.length === 0;

            const next: OverflowState = {
                collapsed,
                overflowed: overflowed.flatMap((child) => itemKeyOf(child) ?? []),
            };

            setState((previous) => (sameState(previous, next) ? previous : next));
        };

        updateRef.current = update;
        update();

        const mutationObserver = new MutationObserver(update);

        mutationObserver.observe(row, {
            childList: true,
            subtree: true,
            characterData: true,
            attributeFilter: ['class', 'data-status', 'aria-current'],
        });

        const resizeObserver = new ResizeObserver(update);

        resizeObserver.observe(root);

        void document.fonts?.ready.then(() => mounted && update());

        return () => {
            mounted = false;
            updateRef.current = undefined;
            mutationObserver.disconnect();
            resizeObserver.disconnect();
        };
    }, [rootRef, rowRef, moreRef]);

    const movable = [...policy.movable].join('\n');

    // A model change that leaves the row's DOM alone still changes what may move.
    useLayoutEffect(() => updateRef.current?.(), [movable, policy.active]);

    return state;
}

function MenuEntry({
    item,
    render,
}: {
    item: NavItem;
    render: (item: NavItem, state: SubNavigationItemState) => React.ReactNode;
}) {
    const navigate = useNavigate();
    const active = useNavItemActive()(item);

    return (
        <DropdownMenu.Item
            className={cn(active && 'bg-muted')}
            onClick={() => void navigate({ to: item.to as never, params: item.params as never, hash: item.hash })}
        >
            <span className='inline-flex items-center gap-2'>
                {render(item, { active, badge: navItemBadge(item), placement: 'menu' })}
            </span>
        </DropdownMenu.Item>
    );
}

/**
 * An area's horizontal navigation. Items that do not fit move into a "More" menu, and when even
 * the pinned ones do not, the row collapses behind a toggle. `<html data-sub-navigation="side">`
 * turns it into a sidebar.
 */
const SubNavigation = ({
    items,
    before,
    after,
    canOverflow = defaultOverflow,
    renderItem = defaultRender,
    className,
    ...props
}: SubNavigationProps) => {
    const rootRef = useRef<HTMLDivElement>(null);
    const rowRef = useRef<HTMLDivElement>(null);
    const moreRef = useRef<HTMLSpanElement>(null);
    const rowId = useId();
    const [open, setOpen] = useState(false);
    const isActive = useNavItemActive();
    const current = useActiveNavItem(items);
    const { overflowed, collapsed } = useOverflowNavigation(rootRef, rowRef, moreRef, {
        movable: new Set(items.filter(canOverflow).map(navItemKey)),
        active: current && navItemKey(current),
    });
    const expanded = collapsed && open;
    const menu = items.filter((item) => overflowed.includes(navItemKey(item)));

    return (
        <div
            ref={rootRef}
            className={cn(subNavigationClass, sideNavigationClass, collapsedNavigationClass, className)}
            data-open={expanded ? '' : undefined}
            {...props}
        >
            <button
                type='button'
                onClick={() => setOpen((value) => !value)}
                aria-expanded={expanded}
                aria-controls={rowId}
                className='mx-auto hidden w-full max-w-panel cursor-pointer items-center gap-3 px-6 py-3 text-sm text-foreground'
            >
                <Icon icon={Menu} className='h-4 w-4 shrink-0' aria-hidden />
                <span className='min-w-0 flex-1 truncate text-left'>{current?.label || 'Navigation'}</span>
                <Icon
                    icon={ChevronDown}
                    className={cn('h-4 w-4 shrink-0 transition-transform', expanded && 'rotate-180')}
                    aria-hidden
                />
            </button>
            <div ref={rowRef} id={rowId} onClick={() => setOpen(false)}>
                {before}
                <Navigation items={items}>
                    {(item, state) => (
                        <NavigationLink item={item} data-nav-item={navItemKey(item)}>
                            {renderItem(item, { ...state, placement: 'row' })}
                        </NavigationLink>
                    )}
                </Navigation>
                {after}
                <span ref={moreRef} className='ml-2 shrink-0'>
                    <DropdownMenu
                        openOnHover
                        triggerClassName={cn(
                            'inline-flex cursor-pointer items-center gap-1 whitespace-nowrap px-4 py-3 text-muted-foreground transition-colors duration-150 hover:text-foreground',
                            menu.some(isActive) && 'text-foreground shadow-navigation-active'
                        )}
                        triggerContent={
                            <>
                                More
                                <Icon icon={ChevronDown} aria-hidden />
                            </>
                        }
                    >
                        {menu.map((item) => (
                            <MenuEntry key={navItemKey(item)} item={item} render={renderItem} />
                        ))}
                    </DropdownMenu>
                </span>
            </div>
        </div>
    );
};

SubNavigation.displayName = 'SubNavigation';

export default SubNavigation;
