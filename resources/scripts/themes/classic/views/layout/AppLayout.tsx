import { Layers, LogOut, Moon, Sun, UserCog } from 'lucide-react';
import { Avatar, DropdownMenu, Icon, PanelLink, SpinnerOverlay, Tooltip } from '@/sdk';
import { Main, Slot, View, type AppLayoutLink, type AppLayoutModel, type ViewProps } from '@/sdk/theme';

const navItemClass = [
    'flex h-full shrink-0 items-center px-3 sm:px-6 no-underline text-muted-foreground cursor-pointer',
    'transition-[background-color,border-color,color,box-shadow] duration-150 hover:bg-popover hover:border-border active:bg-popover/80',
    'hover:text-foreground active:text-foreground',
    'hover:shadow-navigation-active active:shadow-navigation-active',
    'data-[status=active]:shadow-navigation-active',
].join(' ');

const schemeLabels = { dark: 'Dark', light: 'Light' } as const;

function LinkIcon({ link, user }: { link: AppLayoutLink; user: AppLayoutModel['user'] }) {
    if (link.id === 'account') {
        return (
            <span className='flex items-center w-5 h-5'>
                <Avatar name={user.uuid} image={user.avatar} />
            </span>
        );
    }

    return <Icon icon={link.id === 'admin' ? UserCog : Layers} />;
}

/** Only rendered when the theme offers more than one scheme. */
function SchemeMenu({
    model,
    setScheme,
}: {
    model: AppLayoutModel;
    setScheme: NonNullable<AppLayoutModel['setScheme']>;
}) {
    return (
        <DropdownMenu
            triggerClassName={navItemClass}
            triggerContent={
                <>
                    <Icon icon={model.scheme === 'light' ? Sun : Moon} aria-hidden />
                    <span className='sr-only'>Color scheme</span>
                </>
            }
        >
            {model.schemes.map((scheme) => (
                <DropdownMenu.Item key={scheme} checked={scheme === model.preference} onClick={() => setScheme(scheme)}>
                    {schemeLabels[scheme]}
                </DropdownMenu.Item>
            ))}
            <DropdownMenu.Item checked={model.preference === 'system'} onClick={() => setScheme('system')}>
                System
            </DropdownMenu.Item>
        </DropdownMenu>
    );
}

function NavigationBar({ model }: { model: AppLayoutModel }) {
    return (
        <div className='w-full bg-background shadow-md overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden'>
            <SpinnerOverlay visible={model.signingOut} />
            <div className='mx-auto flex h-14 w-full max-w-panel items-center'>
                <div id='logo' className='flex-1 min-w-0'>
                    <PanelLink
                        destination={{ to: '/' }}
                        className='block truncate text-xl sm:text-2xl font-header font-medium px-4 no-underline text-foreground hover:text-accent transition-colors duration-150'
                    >
                        {model.brand.logoUrl ? (
                            <img src={model.brand.logoUrl} alt={model.brand.name} className='h-8 w-auto' />
                        ) : (
                            model.brand.name
                        )}
                    </PanelLink>
                </div>
                <div className='flex h-full shrink-0 items-center justify-center'>
                    <Slot name='nav.items.before' />
                    <View name='app.search' className={navItemClass} />
                    {model.links.map((link) => (
                        <Tooltip key={link.id} placement='bottom' content={link.label}>
                            <PanelLink
                                destination={link.destination}
                                exact={link.exact}
                                className={navItemClass}
                                aria-label={link.label}
                            >
                                <LinkIcon link={link} user={model.user} />
                            </PanelLink>
                        </Tooltip>
                    ))}
                    {model.setScheme && <SchemeMenu model={model} setScheme={model.setScheme} />}
                    <Tooltip placement='bottom' content='Sign Out'>
                        <button type='button' aria-label='Sign Out' onClick={model.signOut} className={navItemClass}>
                            <Icon icon={LogOut} />
                        </button>
                    </Tooltip>
                    <Slot name='nav.items.after' />
                </div>
            </div>
        </div>
    );
}

export default function AppLayout({ model, className, children }: ViewProps<'layout.app'>) {
    return (
        <div className={className}>
            <NavigationBar model={model} />
            <Main>{children}</Main>
        </div>
    );
}
