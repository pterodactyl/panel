import { NamedIcon, cn } from '@pterodactyl/sdk';
import { Main, Navigation, NavigationLink, Slot, View, type ViewProps } from '@pterodactyl/sdk/theme';

const tabClass =
    'flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-sm text-muted-foreground no-underline hover:text-foreground';

/** The server's name with its power controls in the header, then its pages as tabs. */
export default function ServerLayout({ model, children }: ViewProps<'layout.server'>) {
    const items = model.adminLink ? [...model.nav.items, model.adminLink] : model.nav.items;

    return (
        <div className='mx-auto w-full max-w-panel px-4 py-6 lg:px-8'>
            <header className='glass mb-4 flex flex-wrap items-center gap-4 rounded-2xl px-5 py-4'>
                <div className='min-w-0 flex-1'>
                    <h1 className='truncate text-xl font-semibold'>{model.name}</h1>
                    <p className='font-mono text-xs text-muted-foreground'>{model.identifier}</p>
                </div>
                {/* Power controls make no sense while the server installs, transfers or restores. */}
                {model.conflict === null && <View name='server.power' />}
            </header>
            <nav aria-label='Server' className='mb-6 flex gap-1 overflow-x-auto'>
                <Slot name='server.navigation.before' data={model.server} />
                <Navigation items={items}>
                    {(item, { active, badge }) => (
                        <NavigationLink item={item} className={cn(tabClass, active && 'bg-primary/15 text-foreground')}>
                            {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                            {item.label}
                            {badge}
                        </NavigationLink>
                    )}
                </Navigation>
                <Slot name='server.navigation.after' data={model.server} />
            </nav>
            <Main>{children}</Main>
        </div>
    );
}
