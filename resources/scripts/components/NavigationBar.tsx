import { Link } from '@tanstack/react-router';
import { Layers, LogOut, UserCog } from 'lucide-react';
import { useCurrentUser } from '@/api/account/queries';
import { useSiteSettings } from '@/api/settings/queries';
import { useLogout } from '@/api/auth/queries';
import SearchContainer from '@/components/dashboard/search/SearchContainer';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Avatar from '@/components/Avatar';
import Icon from '@/components/elements/Icon';
import Slot from '@/extensions/Slot';
import { interactiveSurfaceClass } from '@/components/ui/styles';

const navItemClass = [
    'flex h-full shrink-0 items-center px-3 sm:px-6 no-underline text-muted-foreground cursor-pointer',
    interactiveSurfaceClass,
    'hover:text-foreground active:text-foreground',
    'hover:shadow-navigation-active active:shadow-navigation-active',
    'data-[status=active]:shadow-navigation-active',
].join(' ');

export default function NavigationBar() {
    const name = useSiteSettings().name;
    const rootAdmin = useCurrentUser().rootAdmin;
    const logout = useLogout();

    const onTriggerLogout = () => logout.mutate({});

    return (
        <div className='w-full bg-background shadow-md overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden'>
            <SpinnerOverlay visible={logout.isPending} />
            <div className='mx-auto flex h-14 w-full max-w-panel items-center'>
                <div id='logo' className='flex-1 min-w-0'>
                    <Link
                        to='/'
                        className='block truncate text-xl sm:text-2xl font-header font-medium px-4 no-underline text-foreground hover:text-accent transition-colors duration-150'
                    >
                        {name}
                    </Link>
                </div>
                <div className='flex h-full shrink-0 items-center justify-center'>
                    <Slot name='nav.items.before' />
                    <SearchContainer className={navItemClass} />
                    <Tooltip placement='bottom' content='Dashboard'>
                        <Link
                            to='/'
                            activeOptions={{ exact: true, includeSearch: false }}
                            className={navItemClass}
                            aria-label='Dashboard'
                        >
                            <Icon icon={Layers} />
                        </Link>
                    </Tooltip>
                    {rootAdmin && (
                        <Tooltip placement='bottom' content='Admin'>
                            <Link to='/panel' className={navItemClass} aria-label='Admin'>
                                <Icon icon={UserCog} />
                            </Link>
                        </Tooltip>
                    )}
                    <Tooltip placement='bottom' content='Account Settings'>
                        <Link to='/account' className={navItemClass} aria-label='Account Settings'>
                            <span className='flex items-center w-5 h-5'>
                                <Avatar.User />
                            </span>
                        </Link>
                    </Tooltip>
                    <Tooltip placement='bottom' content='Sign Out'>
                        <button type='button' aria-label='Sign Out' onClick={onTriggerLogout} className={navItemClass}>
                            <Icon icon={LogOut} />
                        </button>
                    </Tooltip>
                    <Slot name='nav.items.after' />
                </div>
            </div>
        </div>
    );
}
