import type { ReactNode } from 'react';
import { Link, useParams } from '@tanstack/react-router';
import { ExternalLink } from 'lucide-react';
import { useServerQuery } from '@/api/server/queries';
import { useCurrentUser } from '@/api/account/queries';
import SubNavigation from '@/components/elements/SubNavigation';
import SubNavigationLayout from '@/components/elements/SubNavigationLayout';
import NavLink from '@/router/NavLink';
import Can from '@/components/elements/Can';
import Icon from '@/components/elements/Icon';
import { getAreaNav } from '@/router/nav';
import Slot from '@/extensions/Slot';
import NavigationLabel from '@/extensions/NavigationLabel';
import { ScreenGate } from '@/extensions/screenNavigation';
import { Main } from '@/views/a11y';

/** The classic server layout: the server's sub-navigation beside its pages. */
export default function ServerNavigationLayout({ children }: { children?: ReactNode }) {
    const { id } = useParams({ from: '/authenticated/server/$id' });
    const rootAdmin = useCurrentUser().rootAdmin;
    // The engine renders the layout once the server has loaded.
    const server = useServerQuery(id).data!;

    const to = (segment: string) => `/server/${id}${segment ? `/${segment}` : ''}`;

    return (
        <SubNavigationLayout
            navigation={
                <SubNavigation>
                    <Slot name='server.navigation.before' data={server} />
                    {getAreaNav('server').map(({ segment, label, exact, permission, ...meta }) => (
                        <ScreenGate key={segment || '/'} screen={meta.screen}>
                            {permission ? (
                                <Can action={permission} matchAny>
                                    <NavLink
                                        to={to(segment)}
                                        exact={exact}
                                        hash={segment === 'files' ? '/' : ''}
                                        data-core={meta.screen ? undefined : ''}
                                    >
                                        <NavigationLabel label={label} {...meta} />
                                    </NavLink>
                                </Can>
                            ) : (
                                <NavLink
                                    to={to(segment)}
                                    exact={exact}
                                    hash={segment === 'files' ? '/' : ''}
                                    data-core={meta.screen ? undefined : ''}
                                >
                                    <NavigationLabel label={label} {...meta} />
                                </NavLink>
                            )}
                        </ScreenGate>
                    ))}
                    {rootAdmin && (
                        <Link
                            to='/panel/servers/$id'
                            params={{ id: server.attributes.internal_id }}
                            aria-label='Open server administration'
                            title='Open server administration'
                        >
                            <span className='inline-flex items-center gap-2 align-top'>
                                <Icon icon={ExternalLink} />
                                <span className='hidden in-data-collapsed:inline in-data-overflowed:inline'>
                                    Open Admin Area
                                </span>
                            </span>
                        </Link>
                    )}
                    <Slot name='server.navigation.after' data={server} />
                </SubNavigation>
            }
        >
            <Main>{children}</Main>
        </SubNavigationLayout>
    );
}
