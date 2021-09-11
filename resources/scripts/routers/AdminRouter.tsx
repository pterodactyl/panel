import { State, useStoreState } from 'easy-peasy';
import React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import tw, { styled } from 'twin.macro';
import { ApplicationStore } from '@/state';
import { AdminContext } from '@/state/admin';
import { breakpoint } from '@/theme';
import OverviewContainer from '@/components/admin/overview/OverviewContainer';
import SettingsContainer from '@/components/admin/settings/SettingsContainer';
import DatabasesContainer from '@/components/admin/databases/DatabasesContainer';
import NewDatabaseContainer from '@/components/admin/databases/NewDatabaseContainer';
import DatabaseEditContainer from '@/components/admin/databases/DatabaseEditContainer';
import NodesContainer from '@/components/admin/nodes/NodesContainer';
import NewNodeContainer from '@/components/admin/nodes/NewNodeContainer';
import NodeRouter from '@/components/admin/nodes/NodeRouter';
import LocationsContainer from '@/components/admin/locations/LocationsContainer';
import LocationEditContainer from '@/components/admin/locations/LocationEditContainer';
import ServersContainer from '@/components/admin/servers/ServersContainer';
import NewServerContainer from '@/components/admin/servers/NewServerContainer';
import UsersContainer from '@/components/admin/users/UsersContainer';
import NewUserContainer from '@/components/admin/users/NewUserContainer';
import UserRouter from '@/components/admin/users/UserRouter';
import RolesContainer from '@/components/admin/roles/RolesContainer';
import RoleEditContainer from '@/components/admin/roles/RoleEditContainer';
import NestsContainer from '@/components/admin/nests/NestsContainer';
import NestEditContainer from '@/components/admin/nests/NestEditContainer';
import MountsContainer from '@/components/admin/mounts/MountsContainer';
import NewMountContainer from '@/components/admin/mounts/NewMountContainer';
import MountEditContainer from '@/components/admin/mounts/MountEditContainer';
import EggRouter from '@/components/admin/nests/eggs/EggRouter';
import ServerRouter from '@/components/admin/servers/ServerRouter';
import { NotFound } from '@/components/elements/ScreenBlock';
import { usePersistedState } from '@/plugins/usePersistedState';

const Sidebar = styled.div<{ collapsed?: boolean }>`
    ${tw`fixed h-screen hidden md:flex flex-col items-center flex-shrink-0 bg-neutral-900 overflow-x-hidden transition-all duration-250 ease-linear`};
    ${props => props.collapsed ? 'width: 70px' : 'width: 287px'};

    & > div.header {
        ${tw`h-16 w-full flex flex-col items-center justify-center mt-1 mb-3 select-none cursor-pointer`};
    }

    & > div.wrapper {
        ${tw`w-full flex flex-col px-4`};

        & > span {
            height: 18px;
            ${tw`font-header font-medium text-xs text-neutral-300 whitespace-nowrap uppercase ml-4 mb-1 select-none`};
            ${props => props.collapsed && tw`opacity-0`};

            &:not(:first-of-type) {
                ${tw`mt-4`};
            }
        }

        & > a {
            ${tw`h-10 w-full flex flex-row items-center text-neutral-300 cursor-pointer select-none`};
            ${props => props.collapsed ? tw`justify-center` : tw`px-4`};

            & > svg {
                ${tw`h-6 w-6 flex flex-shrink-0`};
            }

            & > span {
                ${props => props.collapsed ? tw`hidden` : tw`font-header font-medium text-lg whitespace-nowrap leading-none ml-3`};
            }

            &:hover {
                ${tw`text-neutral-50`};
            }

            &:active, &.active {
                ${tw`text-neutral-50 bg-neutral-800 rounded`};
            }
        }
    }

    & > a {
        ${tw`h-10 w-full flex flex-row items-center text-neutral-300 cursor-pointer select-none`};
        ${props => props.collapsed ? tw`justify-center px-4` : tw`px-8`};

        & > svg {
            ${tw`h-6 w-6 flex flex-shrink-0`};
        }

        & > span {
            ${props => props.collapsed ? tw`hidden` : tw`font-header font-medium text-lg whitespace-nowrap leading-none ml-3`};
        }

        &:hover {
            ${tw`text-neutral-50`};
        }
    }

    & > div.user {
        ${tw`h-16 w-full flex items-center bg-neutral-700 justify-center`};

        & > div, a {
            ${props => props.collapsed && tw`hidden`};
        }
    }
`;

const Container = styled.div<{ collapsed?: boolean }>`
    ${tw`w-full flex flex-col items-center transition-all duration-250 ease-linear`};
    ${props => props.collapsed ?
        breakpoint('md')`padding-left: 70px`
        :
        breakpoint('md')`padding-left: 287px`};
`;

const AdminRouter = ({ location, match }: RouteComponentProps) => {
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);
    const applicationName = useStoreState((state: ApplicationStore) => state.settings.data!.name);

    const uuid = useStoreState(state => state.user.data!.uuid);
    const [ collapsed, setCollapsed ] = usePersistedState<boolean>(`${uuid}:admin_sidebar_collapsed`, false);

    return (
        <div css={tw`h-screen w-screen overflow-x-hidden flex flex-col md:flex-row`}>
            <Sidebar collapsed={collapsed}>
                <div className={'header'} onClick={ () => { setCollapsed(!collapsed); } }>
                    { !collapsed ?
                        <h1 css={tw`text-2xl text-neutral-50 whitespace-nowrap font-medium`}>{applicationName}</h1>
                        :
                        <img src={'/favicons/android-icon-48x48.png'} alt={'Pterodactyl Icon'} />
                    }
                </div>

                <div className={'wrapper'}>
                    <span>Administration</span>

                    <NavLink to={`${match.url}`} exact>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <span>Overview</span>
                    </NavLink>
                    <NavLink to={`${match.url}/settings`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span>Settings</span>
                    </NavLink>

                    <span>Management</span>

                    <NavLink to={`${match.url}/databases`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                        <span>Databases</span>
                    </NavLink>
                    <NavLink to={`${match.url}/locations`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Locations</span>
                    </NavLink>
                    <NavLink to={`${match.url}/nodes`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                        <span>Nodes</span>
                    </NavLink>
                    <NavLink to={`${match.url}/servers`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span>Servers</span>
                    </NavLink>
                    <NavLink to={`${match.url}/users`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <span>Users</span>
                    </NavLink>
                    <NavLink to={`${match.url}/roles`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <span>Roles</span>
                    </NavLink>

                    <span>Service Management</span>

                    <NavLink to={`${match.url}/nests`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        <span>Nests</span>
                    </NavLink>
                    <NavLink to={`${match.url}/mounts`}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                        <span>Mounts</span>
                    </NavLink>
                </div>

                <NavLink to={'/'} css={tw`mt-auto mb-3`}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" css={tw`h-6 w-6`}>
                        <path strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <span>Return</span>
                </NavLink>

                <div className={'user'}>
                    <img src={user !== undefined ? user.avatarURL + '?s=64' : ''} alt="Profile Picture" css={tw`h-10 w-10 rounded-full select-none`} />

                    <div css={tw`flex flex-col ml-3`}>
                        <span css={tw`font-sans font-normal text-sm text-neutral-50 whitespace-nowrap leading-tight select-none`}>{user?.email}</span>
                        <span css={tw`font-header font-normal text-xs text-neutral-300 whitespace-nowrap leading-tight select-none`}>{user?.roleName}</span>
                    </div>
                </div>
            </Sidebar>

            <Container collapsed={collapsed}>
                <div css={tw`md:min-h-screen w-full flex flex-col px-6 md:px-16 py-6 md:py-12`} style={{ maxWidth: '86rem' }}>
                    {/* <TransitionRouter> */}
                    <Switch location={location}>
                        <Route path={`${match.path}`} component={OverviewContainer} exact/>
                        <Route path={`${match.path}/settings`} component={SettingsContainer} exact/>

                        <Route path={`${match.path}/databases`} component={DatabasesContainer} exact/>
                        <Route path={`${match.path}/databases/new`} component={NewDatabaseContainer} exact/>
                        <Route
                            path={`${match.path}/databases/:id`}
                            component={DatabaseEditContainer}
                            exact
                        />

                        <Route path={`${match.path}/locations`} component={LocationsContainer} exact/>
                        <Route
                            path={`${match.path}/locations/:id`}
                            component={LocationEditContainer}
                            exact
                        />

                        <Route path={`${match.path}/nodes`} component={NodesContainer} exact/>
                        <Route path={`${match.path}/nodes/new`} component={NewNodeContainer} exact/>
                        <Route
                            path={`${match.path}/nodes/:id`}
                            component={NodeRouter}
                        />

                        <Route path={`${match.path}/servers`} component={ServersContainer} exact/>
                        <Route path={`${match.path}/servers/new`} component={NewServerContainer} exact/>
                        <Route
                            path={`${match.path}/servers/:id`}
                            component={ServerRouter}
                        />

                        <Route path={`${match.path}/users`} component={UsersContainer} exact/>
                        <Route path={`${match.path}/users/new`} component={NewUserContainer} exact/>
                        <Route
                            path={`${match.path}/users/:id`}
                            component={UserRouter}
                        />

                        <Route path={`${match.path}/roles`} component={RolesContainer} exact/>
                        <Route
                            path={`${match.path}/roles/:id`}
                            component={RoleEditContainer}
                            exact
                        />

                        <Route path={`${match.path}/nests`} component={NestsContainer} exact/>
                        <Route
                            path={`${match.path}/nests/:nestId`}
                            component={NestEditContainer}
                            exact
                        />
                        <Route
                            path={`${match.path}/nests/:nestId/eggs/:id`}
                            component={EggRouter}
                        />

                        <Route path={`${match.path}/mounts`} component={MountsContainer} exact/>
                        <Route path={`${match.path}/mounts/new`} component={NewMountContainer} exact/>
                        <Route
                            path={`${match.path}/mounts/:id`}
                            component={MountEditContainer}
                            exact
                        />

                        <Route path={'*'} component={NotFound}/>
                    </Switch>
                    {/* </TransitionRouter> */}
                </div>
            </Container>
        </div>
    );
};

export default (props: RouteComponentProps<any>) => (
    <AdminContext.Provider>
        <AdminRouter {...props}/>
    </AdminContext.Provider>
);
