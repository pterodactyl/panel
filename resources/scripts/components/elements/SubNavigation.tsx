import React, { useLayoutEffect, useRef, useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/cn';
import Icon from '@/components/elements/Icon';
import DropdownMenu from '@/components/elements/dropdown/DropdownMenu';

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

const activeSelector = '.active, [data-status=active], [aria-current=page]';

interface OverflowItem {
    key: number;
    element: HTMLElement;
    html: string;
    label: string;
    active: boolean;
}

const elementKeys = new WeakMap<HTMLElement, number>();
let lastElementKey = 0;

const keyFor = (element: HTMLElement) => {
    const key = elementKeys.get(element) ?? ++lastElementKey;
    elementKeys.set(element, key);
    return key;
};

const sameItems = (previous: OverflowItem[], next: OverflowItem[]) =>
    previous.length === next.length &&
    previous.every(
        (item, index) =>
            item.element === next[index].element && item.html === next[index].html && item.active === next[index].active
    );

const activate = (element: HTMLElement) =>
    (element.matches('a, button') ? element : (element.querySelector<HTMLElement>('a, button') ?? element)).click();

function useOverflowItems(
    rowRef: React.RefObject<HTMLDivElement | null>,
    moreRef: React.RefObject<HTMLSpanElement | null>
) {
    const [items, setItems] = useState<OverflowItem[]>([]);

    useLayoutEffect(() => {
        const row = rowRef.current;
        const more = moreRef.current;

        if (!row || !more) {
            return;
        }

        let mounted = true;

        const update = () => {
            const children = Array.from(row.children).filter(
                (child): child is HTMLElement => child !== more && child instanceof HTMLElement
            );

            children.forEach((child) => delete child.dataset.overflowed);
            more.hidden = true;

            let overflowed: HTMLElement[] = [];
            const style = getComputedStyle(row);

            if (style.flexDirection !== 'column') {
                const rect = row.getBoundingClientRect();
                const available = rect.width - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
                let edge = rect.left + parseFloat(style.paddingLeft);
                const widths = children.map((child) => {
                    const right = child.getBoundingClientRect().right;
                    const width = Math.max(0, right - edge);
                    edge = Math.max(edge, right);
                    return width;
                });
                const total = widths.reduce((sum, width) => sum + width, 0);

                if (total > available) {
                    more.hidden = false;
                    const budget =
                        available - more.getBoundingClientRect().width - parseFloat(getComputedStyle(more).marginLeft);

                    let used = 0;
                    let count = 0;
                    while (count < children.length && used + widths[count] <= budget) {
                        used += widths[count];
                        count++;
                    }

                    const visible = children.slice(0, count);
                    const active = children.findIndex(
                        (child, index) =>
                            index >= count &&
                            (child.matches(activeSelector) || child.querySelector(activeSelector) !== null)
                    );

                    if (active !== -1) {
                        while (visible.length > 0 && used + widths[active] > budget) {
                            used -= widths[visible.length - 1];
                            visible.pop();
                        }
                        visible.push(children[active]);
                    }

                    overflowed = children.filter((child) => !visible.includes(child));
                }
            }

            overflowed.forEach((child) => (child.dataset.overflowed = ''));
            more.hidden = overflowed.length === 0;

            const next = overflowed.map((element) => ({
                key: keyFor(element),
                element,
                html: element.innerHTML,
                label: element.textContent?.trim() ? '' : element.getAttribute('aria-label') || element.title,
                active: element.matches(activeSelector) || element.querySelector(activeSelector) !== null,
            }));

            setItems((previous) => (sameItems(previous, next) ? previous : next));
        };

        update();

        const mutationObserver = new MutationObserver(update);
        mutationObserver.observe(row, {
            childList: true,
            subtree: true,
            characterData: true,
            attributeFilter: ['class', 'data-status', 'aria-current'],
        });

        const resizeObserver = new ResizeObserver(update);
        resizeObserver.observe(row);

        document.fonts?.ready.then(() => mounted && update());

        return () => {
            mounted = false;
            mutationObserver.disconnect();
            resizeObserver.disconnect();
        };
    }, [rowRef, moreRef]);

    return items;
}

const SubNavigation = ({ className, children, ...props }: React.ComponentProps<'div'>) => {
    const rowRef = useRef<HTMLDivElement>(null);
    const moreRef = useRef<HTMLSpanElement>(null);
    const items = useOverflowItems(rowRef, moreRef);

    return (
        <div className={cn(subNavigationClass, sideNavigationClass, className)} {...props}>
            <div ref={rowRef}>
                {children}
                <span ref={moreRef} className={'ml-2 shrink-0'}>
                    <DropdownMenu
                        openOnHover
                        triggerClassName={cn(
                            'inline-flex cursor-pointer items-center gap-1 whitespace-nowrap px-4 py-3 text-muted-foreground transition-colors duration-150 hover:text-foreground',
                            items.some((item) => item.active) && 'text-foreground shadow-navigation-active'
                        )}
                        triggerContent={
                            <>
                                More
                                <Icon icon={ChevronDown} aria-hidden />
                            </>
                        }
                    >
                        {items.map((item) => (
                            <DropdownMenu.Item
                                key={item.key}
                                className={cn(item.active && 'bg-muted')}
                                onClick={() => activate(item.element)}
                            >
                                <span className={'inline-flex items-center gap-2'}>
                                    <span dangerouslySetInnerHTML={{ __html: item.html }} />
                                    {item.label && <span>{item.label}</span>}
                                </span>
                            </DropdownMenu.Item>
                        ))}
                    </DropdownMenu>
                </span>
            </div>
        </div>
    );
};
SubNavigation.displayName = 'SubNavigation';

export default SubNavigation;
