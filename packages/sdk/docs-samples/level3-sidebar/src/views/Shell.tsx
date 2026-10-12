import { LayoutGrid, LogOut, Moon, Shield, Sun, UserRound, type LucideIcon } from 'lucide-react';
import { Avatar, PanelLink, SpinnerOverlay } from '@pterodactyl/sdk';
import { Main, Slot, View, type AppLayoutLink, type ViewProps } from '@pterodactyl/sdk/theme';

const icons: Record<AppLayoutLink['id'], LucideIcon> = { dashboard: LayoutGrid, admin: Shield, account: UserRound };

const item = [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground',
].join(' ');

/** A sidebar on large screens and a top bar below them, with the page beside it. */
export default function Shell({ model, children }: ViewProps<'layout.app'>) {
    const { brand, user, scheme, setScheme } = model;

    return (
        <div className='flex min-h-dvh flex-col lg:flex-row'>
            <SpinnerOverlay visible={model.signingOut} fixed />
            <aside className='flex shrink-0 items-center gap-2 bg-sidebar p-3 lg:sticky lg:top-0 lg:h-dvh lg:w-sidebar lg:flex-col lg:items-stretch lg:p-4'>
                <PanelLink destination={{ to: '/' }} className='flex h-10 items-center px-3 font-semibold no-underline'>
                    {brand.logoUrl ? <img src={brand.logoUrl} alt={brand.name} className='h-8 w-auto' /> : brand.name}
                </PanelLink>
                <nav aria-label='Main' className='flex flex-1 gap-1 lg:flex-col'>
                    <Slot name='nav.items.before' />
                    {model.links.map((link) => {
                        const Glyph = icons[link.id];

                        return (
                            <PanelLink key={link.id} destination={link.destination} exact={link.exact} className={item}>
                                <Glyph className='size-4' aria-hidden />
                                <span className='max-lg:sr-only'>{link.label}</span>
                            </PanelLink>
                        );
                    })}
                    <Slot name='nav.items.after' />
                </nav>
                <View name='app.search' className={item} />
                <div className='flex items-center gap-2'>
                    <Avatar name={user.uuid} size={28} />
                    <span className='min-w-0 flex-1 truncate text-sm max-lg:sr-only'>{user.username}</span>
                    {/* setScheme is null unless the theme offers both schemes. */}
                    {setScheme && (
                        <button
                            type='button'
                            aria-label={scheme === 'dark' ? 'Use the light scheme' : 'Use the dark scheme'}
                            onClick={() => setScheme(scheme === 'dark' ? 'light' : 'dark')}
                            className={item}
                        >
                            {scheme === 'dark' ? (
                                <Sun className='size-4' aria-hidden />
                            ) : (
                                <Moon className='size-4' aria-hidden />
                            )}
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
