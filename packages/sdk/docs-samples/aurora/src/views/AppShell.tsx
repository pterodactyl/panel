import { LayoutGrid, LogOut, Shield, UserRound, type LucideIcon } from 'lucide-react';
import { Avatar, PanelLink, SpinnerOverlay, Tooltip, cn } from '@pterodactyl/sdk';
import { Main, Slot, View, type AppLayoutLink, type AppLayoutModel, type ViewProps } from '@pterodactyl/sdk/theme';
import SchemeSwitch from '../SchemeSwitch';

const icons: Record<AppLayoutLink['id'], LucideIcon> = { dashboard: LayoutGrid, admin: Shield, account: UserRound };

const linkClass = cn(
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground no-underline transition',
    'hover:bg-popover hover:text-foreground data-[status=active]:bg-primary/15 data-[status=active]:text-foreground'
);

function Brand({ brand }: { brand: AppLayoutModel['brand'] }) {
    return (
        <PanelLink destination={{ to: '/' }} className='flex h-10 items-center px-3 font-semibold no-underline'>
            {brand.logoUrl ? <img src={brand.logoUrl} alt={brand.name} className='h-8 w-auto' /> : brand.name}
        </PanelLink>
    );
}

function Links({ model }: { model: AppLayoutModel }) {
    return (
        <>
            <Slot name='nav.items.before' />
            {model.links.map((link) => {
                const Glyph = icons[link.id];

                return (
                    <PanelLink key={link.id} destination={link.destination} exact={link.exact} className={linkClass}>
                        <Glyph className='size-4' aria-hidden />
                        <span className='max-lg:sr-only'>{link.label}</span>
                    </PanelLink>
                );
            })}
            <Slot name='nav.items.after' />
        </>
    );
}

/** A sidebar on large screens and a top bar below them; the "sidebar" setting picks the side. */
export default function AppShell({ model, children }: ViewProps<'layout.app'>) {
    return (
        <div className='flex min-h-dvh flex-col lg:flex-row sidebar-right:lg:flex-row-reverse'>
            <SpinnerOverlay visible={model.signingOut} fixed />
            <aside className='glass flex shrink-0 items-center gap-2 p-3 lg:sticky lg:top-0 lg:h-dvh lg:w-sidebar lg:flex-col lg:items-stretch lg:gap-6 lg:p-4'>
                <Brand brand={model.brand} />
                <nav aria-label='Main' className='flex flex-1 gap-1 lg:flex-none lg:flex-col'>
                    <Links model={model} />
                </nav>
                <View name='app.search' className={linkClass} />
                <div className='flex items-center gap-2 lg:mt-auto'>
                    <Avatar name={model.user.uuid} size={28} />
                    <span className='min-w-0 flex-1 truncate text-sm max-lg:sr-only'>{model.user.username}</span>
                    <SchemeSwitch />
                    <Tooltip content='Sign out'>
                        <button type='button' aria-label='Sign out' onClick={model.signOut} className={linkClass}>
                            <LogOut className='size-4' aria-hidden />
                        </button>
                    </Tooltip>
                </div>
            </aside>
            <Main className='min-w-0 flex-1'>{children}</Main>
        </div>
    );
}
