import { useEffect, useState } from 'react';
import { LogOut, Menu, Moon, Sun } from 'lucide-react';
import { Avatar, NamedIcon, PanelLink, SpinnerOverlay, cn, usePanelLocation } from '@pterodactyl/sdk';
import { Main, Slot, View, type ViewProps } from '@pterodactyl/sdk/theme';
import AreaNav from './AreaNav';

const item = [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground',
].join(' ');

/** A sidebar beside the page on large screens, and a drawer behind a menu button below them. */
export default function Shell({ model, children }: ViewProps<'layout.app'>) {
    const { brand, user, scheme, setScheme } = model;
    const SchemeIcon = scheme === 'dark' ? Sun : Moon;
    const [open, setOpen] = useState(false);
    const { pathname } = usePanelLocation();

    // Close the drawer when one of its links navigates.
    useEffect(() => setOpen(false), [pathname]);

    return (
        <div className='min-h-dvh lg:flex'>
            <SpinnerOverlay visible={model.signingOut} fixed />
            <button
                type='button'
                aria-expanded={open}
                aria-controls='sidebar'
                onClick={() => setOpen(!open)}
                className={cn(item, 'm-2 lg:hidden')}
            >
                <Menu className='size-4' aria-hidden />
                Menu
            </button>
            <aside
                id='sidebar'
                className={cn(
                    'flex-col gap-4 bg-sidebar p-4 lg:sticky lg:top-0 lg:flex lg:h-dvh lg:w-sidebar lg:shrink-0 lg:overflow-y-auto',
                    open ? 'flex' : 'hidden'
                )}
            >
                <PanelLink destination={{ to: '/' }} className='flex h-10 items-center px-3 font-semibold no-underline'>
                    {brand.logoUrl ? <img src={brand.logoUrl} alt={brand.name} className='h-8 w-auto' /> : brand.name}
                </PanelLink>
                <nav aria-label='Main' className='flex flex-col gap-1'>
                    <Slot name='nav.items.before' />
                    {model.links.map((link) => (
                        <PanelLink key={link.id} destination={link.destination} exact={link.exact} className={item}>
                            <NamedIcon name={link.icon} size={16} aria-hidden />
                            {link.label}
                        </PanelLink>
                    ))}
                    <Slot name='nav.items.after' />
                </nav>
                <AreaNav area={model.area} />
                <View name='app.search' className={item} />
                <div className='mt-auto flex items-center gap-2'>
                    <Avatar name={user.uuid} size={28} />
                    <span className='min-w-0 flex-1 truncate text-sm'>{user.username}</span>
                    {/* setScheme is null unless the theme offers both schemes. */}
                    {setScheme && (
                        <button
                            type='button'
                            aria-label={scheme === 'dark' ? 'Use the light scheme' : 'Use the dark scheme'}
                            onClick={() => setScheme(scheme === 'dark' ? 'light' : 'dark')}
                            className={item}
                        >
                            <SchemeIcon className='size-4' aria-hidden />
                        </button>
                    )}
                    <button type='button' aria-label='Sign out' onClick={model.signOut} className={item}>
                        <LogOut className='size-4' aria-hidden />
                    </button>
                </div>
            </aside>
            <Main className='min-w-0 flex-1'>{children}</Main>
        </div>
    );
}
