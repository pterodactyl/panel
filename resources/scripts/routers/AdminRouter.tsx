import { State, useStoreState } from 'easy-peasy';
import React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import tw from 'twin.macro';
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
import ServerRouter from '@/components/admin/servers/ServerRouter';
import NewUserContainer from '@/components/admin/users/NewUserContainer';
import UserRouter from '@/components/admin/users/UserRouter';
import RolesContainer from '@/components/admin/roles/RolesContainer';
import RoleEditContainer from '@/components/admin/roles/RoleEditContainer';
import NestsContainer from '@/components/admin/nests/NestsContainer';
import NestEditContainer from '@/components/admin/nests/NestEditContainer';
import NewEggContainer from '@/components/admin/nests/NewEggContainer';
import EggRouter from '@/components/admin/nests/eggs/EggRouter';
import MountsContainer from '@/components/admin/mounts/MountsContainer';
import NewMountContainer from '@/components/admin/mounts/NewMountContainer';
import MountEditContainer from '@/components/admin/mounts/MountEditContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import { ApplicationStore } from '@/state';
import { AdminContext } from '@/state/admin';
import {
    CogIcon,
    DatabaseIcon,
    FolderIcon,
    GlobeIcon,
    OfficeBuildingIcon,
    ReplyIcon,
    ServerIcon,
    TerminalIcon,
    UserGroupIcon,
    UsersIcon,
    ViewGridIcon,
} from '@heroicons/react/outline';
import CollapsedIcon from '@/assets/images/pterodactyl.svg';
import Sidebar from '@/components/admin/Sidebar';
import useUserPersistedState from '@/plugins/useUserPersistedState';
import UsersContainer from '@/components/admin/users/UsersContainer';

const AdminRouter = ({ location, match }: RouteComponentProps) => {
    const email = useStoreState((state: State<ApplicationStore>) => state.user.data!.email);
    const roleName = useStoreState((state: State<ApplicationStore>) => state.user.data!.roleName);
    const avatarURL = useStoreState((state: State<ApplicationStore>) => state.user.data!.avatarURL);
    const applicationName = useStoreState((state: ApplicationStore) => state.settings.data!.name);

    const [ collapsed, setCollapsed ] = useUserPersistedState('admin_sidebar_collapsed', false);

    return (
        <div css={tw`h-screen flex`}>
            <Sidebar css={tw`flex-none`} $collapsed={collapsed}>
                <div
                    css={tw`h-16 w-full flex flex-col items-center justify-center mt-1 mb-3 select-none cursor-pointer`}
                    onClick={() => setCollapsed(!collapsed)}
                >
                    {!collapsed ?
                        <h1 css={tw`text-2xl text-neutral-50 whitespace-nowrap font-medium`}>{applicationName}</h1>
                        :
                        <img src={CollapsedIcon} css={tw`mt-4 w-20`} alt={'Pterodactyl Icon'}/>
                    }
                </div>
                <Sidebar.Wrapper>
                    <Sidebar.Section>Administration</Sidebar.Section>
                    <NavLink to={`${match.url}`} exact>
                        <OfficeBuildingIcon/><span>Overview</span>
                    </NavLink>
                    <NavLink to={`${match.url}/settings`}>
                        <CogIcon/><span>Settings</span>
                    </NavLink>
                    <Sidebar.Section>Management</Sidebar.Section>
                    <NavLink to={`${match.url}/databases`}>
                        <DatabaseIcon/><span>Databases</span>
                    </NavLink>
                    <NavLink to={`${match.url}/locations`}>
                        <GlobeIcon/><span>Locations</span>
                    </NavLink>
                    <NavLink to={`${match.url}/nodes`}>
                        <ServerIcon/><span>Nodes</span>
                    </NavLink>
                    <NavLink to={`${match.url}/servers`}>
                        <TerminalIcon/><span>Servers</span>
                    </NavLink>
                    <NavLink to={`${match.url}/users`}>
                        <UsersIcon/><span>Users</span>
                    </NavLink>
                    <NavLink to={`${match.url}/roles`}>
                        <UserGroupIcon/><span>Roles</span>
                    </NavLink>
                    <Sidebar.Section>Service Management</Sidebar.Section>
                    <NavLink to={`${match.url}/nests`}>
                        <ViewGridIcon/><span>Nests</span>
                    </NavLink>
                    <NavLink to={`${match.url}/mounts`}>
                        <FolderIcon/><span>Mounts</span>
                    </NavLink>
                </Sidebar.Wrapper>
                <NavLink to={'/'} css={tw`mt-auto mb-3`}>
                    <ReplyIcon/><span>Return</span>
                </NavLink>
                <Sidebar.User>
                    {avatarURL &&
                    <img src={`${avatarURL}?s=64`} alt="Profile Picture" css={tw`h-10 w-10 rounded-full select-none`}/>
                    }
                    <div css={tw`flex flex-col ml-3`}>
                        <span css={tw`font-sans font-normal text-sm text-neutral-50 whitespace-nowrap leading-tight select-none`}>{email}</span>
                        <span css={tw`font-header font-normal text-xs text-neutral-300 whitespace-nowrap leading-tight select-none`}>{roleName}</span>
                    </div>
                </Sidebar.User>
            </Sidebar>
            <div css={tw`flex-1 overflow-x-hidden px-6 pt-6 lg:px-10 lg:pt-8 xl:px-16 xl:pt-12`}>
                <div css={tw`w-full flex flex-col mx-auto`} style={{ maxWidth: '86rem' }}>
                    <Switch location={location}>
                        <Route path={`${match.path}`} component={OverviewContainer} exact/>
                        <Route path={`${match.path}/settings`} component={SettingsContainer}/>
                        <Route path={`${match.path}/databases`} component={DatabasesContainer} exact/>
                        <Route path={`${match.path}/databases/new`} component={NewDatabaseContainer} exact/>
                        <Route path={`${match.path}/databases/:id`} component={DatabaseEditContainer} exact/>
                        <Route path={`${match.path}/locations`} component={LocationsContainer} exact/>
                        <Route path={`${match.path}/locations/:id`} component={LocationEditContainer} exact/>
                        <Route path={`${match.path}/nodes`} component={NodesContainer} exact/>
                        <Route path={`${match.path}/nodes/new`} component={NewNodeContainer} exact/>
                        <Route path={`${match.path}/nodes/:id`} component={NodeRouter}/>
                        <Route path={`${match.path}/servers`} component={ServersContainer} exact/>
                        <Route path={`${match.path}/servers/new`} component={NewServerContainer} exact/>
                        <Route path={`${match.path}/servers/:id`} component={ServerRouter}/>
                        <Route path={`${match.path}/users`} component={UsersContainer} exact/>
                        <Route path={`${match.path}/users/new`} component={NewUserContainer} exact/>
                        <Route path={`${match.path}/users/:id`} component={UserRouter}/>
                        <Route path={`${match.path}/roles`} component={RolesContainer} exact/>
                        <Route path={`${match.path}/roles/:id`} component={RoleEditContainer} exact/>
                        <Route path={`${match.path}/nests`} component={NestsContainer} exact/>
                        <Route path={`${match.path}/nests/:nestId`} component={NestEditContainer} exact/>
                        <Route path={`${match.path}/nests/:nestId/new`} component={NewEggContainer} exact/>
                        <Route path={`${match.path}/nests/:nestId/eggs/:id`} component={EggRouter}/>
                        <Route path={`${match.path}/mounts`} component={MountsContainer} exact/>
                        <Route path={`${match.path}/mounts/new`} component={NewMountContainer} exact/>
                        <Route path={`${match.path}/mounts/:id`} component={MountEditContainer} exact/>
                        <Route path={'*'} component={NotFound}/>
                    </Switch>
                </div>
            </div>
        </div>
    );
};

export default (props: RouteComponentProps<any>) => (
    <AdminContext.Provider>
        <AdminRouter {...props}/>
    </AdminContext.Provider>
);
