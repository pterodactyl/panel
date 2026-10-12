import { useEffect, useState, type ReactNode } from 'react';
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
    Palette,
    Puzzle,
    Server,
    Settings,
    Tag,
    Users,
    type LucideIcon,
} from 'lucide-react';
import { cn, Icon, NavigationLabel, usePanelLocation } from '@/sdk';
import {
    Main,
    Navigation,
    NavigationLink,
    Slot,
    useActiveNavItem,
    type NavigationModel,
    type NavItem,
    type ViewProps,
} from '@/sdk/theme';

// The panel's own items render these directly, so they never wait for the icon catalog.
const icons = new Map<string, LucideIcon>([
    ['layout-dashboard', LayoutDashboard],
    ['settings', Settings],
    ['users', Users],
    ['map-pin', MapPin],
    ['network', Network],
    ['server', Server],
    ['database', Database],
    ['folder-tree', FolderTree],
    ['egg', Egg],
    ['tag', Tag],
    ['blocks', Blocks],
    ['palette', Palette],
    ['key-round', KeyRound],
]);

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

function ItemLabel({ item, badge }: { item: NavItem; badge: ReactNode }) {
    const glyph = item.extensionId === undefined && item.icon ? icons.get(item.icon) : undefined;

    return (
        <>
            {(glyph !== undefined || !item.icon) && <Icon icon={glyph ?? Puzzle} className='w-4 h-4 shrink-0' />}
            <NavigationLabel label={item.label} group={item.group} icon={glyph ? null : item.icon} badge={badge} />
        </>
    );
}

function AdminSidebar({ nav }: { nav: NavigationModel }) {
    const [open, setOpen] = useState(false);
    const { pathname } = usePanelLocation();
    const current = useActiveNavItem(nav.items);

    useEffect(() => setOpen(false), [pathname]);

    return (
        <div className={cn('w-full shrink-0 px-4 pt-4', 'lg:w-sidebar lg:px-0 lg:pt-0')}>
            <button
                type='button'
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-controls='admin-sidebar-nav'
                className={cn(
                    'flex w-full items-center gap-3 rounded-md bg-card px-3 py-2 text-sm text-foreground shadow-sm',
                    'transition-colors duration-150 hover:bg-popover lg:hidden'
                )}
            >
                <Icon icon={Menu} className='w-4 h-4 shrink-0' />
                <span className='flex-1 min-w-0 truncate text-left'>{current?.label ?? 'Administration'}</span>
                <Icon
                    icon={ChevronDown}
                    className={cn('w-4 h-4 shrink-0 transition-transform', open && 'rotate-180')}
                />
            </button>
            <nav
                id='admin-sidebar-nav'
                aria-label='Administration'
                className={cn(
                    'mt-2 flex-col gap-0.5',
                    'lg:sticky lg:top-0 lg:mt-0 lg:flex lg:max-h-dvh lg:overflow-y-auto lg:px-4 lg:py-6',
                    open ? 'flex' : 'hidden'
                )}
            >
                <div className={extensionItemsClass}>
                    <Slot name='panel.navigation.before' />
                </div>
                <Navigation items={nav.items}>
                    {(item, state) => (
                        <NavigationLink item={item} className={itemClass}>
                            <ItemLabel item={item} badge={state.badge} />
                        </NavigationLink>
                    )}
                </Navigation>
                <div className={extensionItemsClass}>
                    <Slot name='panel.navigation.after' />
                </div>
            </nav>
        </div>
    );
}

export default function AdminLayout({ model, children }: ViewProps<'layout.admin'>) {
    return (
        <div className='mx-auto flex w-full max-w-panel flex-col lg:flex-row'>
            <AdminSidebar nav={model.nav} />
            <div className='min-w-0 flex-1 xl:pl-4'>
                <Main>{children}</Main>
            </div>
        </div>
    );
}
