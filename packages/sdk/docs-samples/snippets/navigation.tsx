import { NamedIcon, cn } from '@pterodactyl/sdk';
import {
    Main,
    Navigation,
    NavigationLink,
    Slot,
    useActiveNavItem,
    useNavigation,
    type ViewProps,
} from '@pterodactyl/sdk/theme';

// #region account
/** The account pages with a vertical menu. `model.nav` lists them, extension screens included. */
export function AccountLayout({ model, children }: ViewProps<'layout.account'>) {
    const current = useActiveNavItem(model.nav.items);

    return (
        <div className='mx-auto flex max-w-panel gap-8 p-6'>
            <nav aria-label='Account' className='w-56 shrink-0 space-y-1'>
                <Slot name='account.navigation.before' />
                <Navigation items={model.nav.items}>
                    {(item, { active, badge }) => (
                        <NavigationLink
                            item={item}
                            className={cn('flex items-center gap-2 rounded-lg px-3 py-2', active && 'bg-primary/15')}
                        >
                            {item.icon && <NamedIcon name={item.icon} size={16} aria-hidden />}
                            <span className='flex-1'>{item.label}</span>
                            {badge}
                        </NavigationLink>
                    )}
                </Navigation>
                <Slot name='account.navigation.after' />
            </nav>
            <Main className='min-w-0 flex-1'>
                <p className='text-sm text-muted-foreground'>{current?.label}</p>
                {children}
            </Main>
        </div>
    );
}
// #endregion account

// #region anywhere
/** The account links anywhere: `useNavigation` reads an area's live navigation. */
export function AccountShortcuts() {
    const { items } = useNavigation('account');

    return (
        <ul>
            {items.map((item) => (
                <li key={`${item.extensionId ?? ''}:${item.id}`}>
                    <NavigationLink item={item}>{item.label}</NavigationLink>
                </li>
            ))}
        </ul>
    );
}
// #endregion anywhere
