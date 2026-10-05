import { useEffect, useState } from 'react';
import { useLocation } from '@tanstack/react-router';
import {
    Blocks,
    ChevronDown,
    Database,
    Egg,
    FolderTree,
    KeyRound,
    LayoutDashboard,
    MapPin,
    Menu,
    Network,
    Puzzle,
    Server,
    Settings,
    Tag,
    Users,
    type LucideIcon,
} from 'lucide-react';
import NavLink from '@/router/NavLink';
import Icon from '@/components/elements/Icon';
import Slot from '@/extensions/Slot';
import NavigationLabel from '@/extensions/NavigationLabel';
import { ScreenGate } from '@/extensions/screenNavigation';
import { getAreaNav, type AreaNavEntry } from '@/router/nav';
import { cn } from '@/lib/cn';

const icons = new Map<string, LucideIcon>([
    ['', LayoutDashboard],
    ['settings', Settings],
    ['users', Users],
    ['locations', MapPin],
    ['nodes', Network],
    ['servers', Server],
    ['databases', Database],
    ['mounts', FolderTree],
    ['eggs', Egg],
    ['tags', Tag],
    ['extensions', Blocks],
    ['api', KeyRound],
]);

const iconFor = (segment: string): LucideIcon => icons.get(segment) ?? Puzzle;

const itemClass = [
    'flex items-center gap-3 rounded-md px-3 py-2 text-sm no-underline',
    'text-muted-foreground transition-colors duration-150',
    'hover:bg-card hover:text-foreground active:bg-popover/80',
    'data-[status=active]:bg-popover data-[status=active]:text-foreground',
].join(' ');

const extensionItemsClass = [
    'contents',
    '[&_a]:flex [&_a]:items-center [&_a]:gap-3 [&_a]:rounded-md [&_a]:py-2 [&_a]:pr-3 [&_a]:pl-10 [&_a]:text-sm [&_a]:no-underline',
    '[&_a]:text-muted-foreground [&_a]:transition-colors [&_a]:duration-150',
    '[&_a:hover]:bg-card [&_a:hover]:text-foreground',
    '[&_a[data-status=active]]:bg-popover [&_a[data-status=active]]:text-foreground',
].join(' ');

const hrefFor = ({ segment }: AreaNavEntry) => `/panel${segment ? `/${segment}` : ''}`;

const activeEntry = (entries: AreaNavEntry[], pathname: string) =>
    entries
        .filter((entry) => {
            const href = hrefFor(entry);

            return entry.exact ? pathname === href : pathname === href || pathname.startsWith(`${href}/`);
        })
        .sort((a, b) => b.segment.length - a.segment.length)[0];

export default function AdminSidebar() {
    const [open, setOpen] = useState(false);
    const { pathname } = useLocation();
    const entries = getAreaNav('admin');

    useEffect(() => setOpen(false), [pathname]);

    const current = activeEntry(entries, pathname);

    return (
        <div className={cn('w-full shrink-0 px-4 pt-4', 'lg:w-sidebar lg:px-0 lg:pt-0')}>
            <button
                type={'button'}
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-controls={'admin-sidebar-nav'}
                className={cn(
                    'flex w-full items-center gap-3 rounded-md bg-card px-3 py-2 text-sm text-foreground shadow-sm',
                    'transition-colors duration-150 hover:bg-popover lg:hidden'
                )}
            >
                <Icon icon={Menu} className={'w-4 h-4 shrink-0'} />
                <span className={'flex-1 min-w-0 truncate text-left'}>{current?.label ?? 'Administration'}</span>
                <Icon
                    icon={ChevronDown}
                    className={cn('w-4 h-4 shrink-0 transition-transform', open && 'rotate-180')}
                />
            </button>
            <nav
                id={'admin-sidebar-nav'}
                aria-label={'Administration'}
                className={cn(
                    'mt-2 flex-col gap-0.5',
                    'lg:sticky lg:top-0 lg:mt-0 lg:flex lg:max-h-dvh lg:overflow-y-auto lg:px-4 lg:py-6',
                    open ? 'flex' : 'hidden'
                )}
            >
                <div className={extensionItemsClass}>
                    <Slot name={'panel.navigation.before'} />
                </div>
                {entries.map((entry) => (
                    <ScreenGate key={entry.segment || '/'} screen={entry.screen}>
                        <NavLink to={hrefFor(entry)} exact={entry.exact ?? false} className={itemClass}>
                            {!entry.icon && <Icon icon={iconFor(entry.segment)} className={'w-4 h-4 shrink-0'} />}
                            <NavigationLabel {...entry} />
                        </NavLink>
                    </ScreenGate>
                ))}
                <div className={extensionItemsClass}>
                    <Slot name={'panel.navigation.after'} />
                </div>
            </nav>
        </div>
    );
}
