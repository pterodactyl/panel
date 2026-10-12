import { Badge, NamedIcon } from '@pterodactyl/sdk';
import {
    Navigation,
    NavigationLink,
    Slot,
    type AccountArea,
    type AdminArea,
    type AppArea,
    type ServerArea,
} from '@pterodactyl/sdk/theme';

type Area = AccountArea | ServerArea | AdminArea;

const link = [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground',
].join(' ');

/** The slots of the area's navigation, which its own layout would render if it drew the navigation. */
function AreaSlot({ area, at }: { area: Area; at: 'before' | 'after' }) {
    if (area.kind === 'server') return <Slot name={`server.navigation.${at}`} data={area.server} />;
    if (area.kind === 'account') return <Slot name={`account.navigation.${at}`} />;
    return <Slot name={`panel.navigation.${at}`} />;
}

/** The pages of the area the user is in: a server's, the account's or the admin area's. */
export default function AreaNav({ area }: { area: AppArea }) {
    if (area.kind === 'dashboard') return null;

    const items = area.kind === 'server' && area.adminLink ? [...area.nav.items, area.adminLink] : area.nav.items;
    const label = { server: 'Server', account: 'Account', admin: 'Administration' }[area.kind];

    return (
        <nav aria-label={label} className='flex flex-col gap-1'>
            {area.kind === 'server' && (
                <p className='flex items-center gap-2 px-3 pb-1 text-sm font-semibold'>
                    <span className='min-w-0 flex-1 truncate'>{area.name}</span>
                    <Badge size='sm' tone={area.state === 'running' ? 'success' : 'neutral'}>
                        {area.state ?? 'unknown'}
                    </Badge>
                </p>
            )}
            <AreaSlot area={area} at='before' />
            <Navigation items={items}>
                {(item, { badge }) => (
                    <NavigationLink item={item} className={link}>
                        {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                        <span className='min-w-0 flex-1 truncate'>{item.label}</span>
                        {badge}
                    </NavigationLink>
                )}
            </Navigation>
            <AreaSlot area={area} at='after' />
        </nav>
    );
}
