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
import { useStoreState } from 'easy-peasy';
import { useState } from 'react';
import { NavLink, Route, Routes } from 'react-router-dom';
import tw from 'twin.macro';

import CollapsedIcon from '@/assets/images/pterodactyl.svg';
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
import type { ApplicationStore } from '@/state';
import Sidebar from '@/components/admin/Sidebar';
// import useUserPersistedState from '@/plugins/useUserPersistedState';
import UsersContainer from '@/components/admin/users/UsersContainer';

function AdminRouter() {
    const email = useStoreState((state: ApplicationStore) => state.user.data!.email);
    const roleName = useStoreState((state: ApplicationStore) => state.user.data!.roleName);
    const avatarURL = useStoreState((state: ApplicationStore) => state.user.data!.avatarURL);
    const applicationName = useStoreState((state: ApplicationStore) => state.settings.data!.name);

    // const [collapsed, setCollapsed] = useUserPersistedState('admin_sidebar_collapsed', false);
    const [collapsed, setCollapsed] = useState<boolean>(false);

    return (
        <div css={tw`h-screen flex`}>
            <Sidebar css={tw`flex-none`} $collapsed={collapsed}>
                <div
                    css={tw`h-16 w-full flex flex-col items-center justify-center mt-1 mb-3 select-none cursor-pointer`}
                    onClick={() => setCollapsed(!collapsed)}
                >
                    {!collapsed ? (
                        <h1 css={tw`text-2xl text-neutral-50 whitespace-nowrap font-medium`}>{applicationName}</h1>
                    ) : (
                        <img src={CollapsedIcon} css={tw`mt-4 w-20`} alt={'Pterodactyl Icon'} />
                    )}
                </div>
                <Sidebar.Wrapper>
                    <Sidebar.Section>Administration</Sidebar.Section>
                    <NavLink to="/admin" end>
                        <OfficeBuildingIcon />
                        <span>Overview</span>
                    </NavLink>
                    <NavLink to="/admin/settings">
                        <CogIcon />
                        <span>Settings</span>
                    </NavLink>
                    <Sidebar.Section>Management</Sidebar.Section>
                    <NavLink to="/admin/databases">
                        <DatabaseIcon />
                        <span>Databases</span>
                    </NavLink>
                    <NavLink to="/admin/locations">
                        <GlobeIcon />
                        <span>Locations</span>
                    </NavLink>
                    <NavLink to="/admin/nodes">
                        <ServerIcon />
                        <span>Nodes</span>
                    </NavLink>
                    <NavLink to="/admin/servers">
                        <TerminalIcon />
                        <span>Servers</span>
                    </NavLink>
                    <NavLink to="/admin/users">
                        <UsersIcon />
                        <span>Users</span>
                    </NavLink>
                    <NavLink to="/admin/roles">
                        <UserGroupIcon />
                        <span>Roles</span>
                    </NavLink>
                    <Sidebar.Section>Service Management</Sidebar.Section>
                    <NavLink to="/admin/nests">
                        <ViewGridIcon />
                        <span>Nests</span>
                    </NavLink>
                    <NavLink to="/admin/mounts">
                        <FolderIcon />
                        <span>Mounts</span>
                    </NavLink>
                </Sidebar.Wrapper>
                <NavLink to="/" css={tw`mt-auto mb-3`}>
                    <ReplyIcon />
                    <span>Return</span>
                </NavLink>
                <Sidebar.User>
                    {avatarURL && (
                        <img
                            src={`${avatarURL}?s=64`}
                            alt="Profile Picture"
                            css={tw`h-10 w-10 rounded-full select-none`}
                        />
                    )}
                    <div css={tw`flex flex-col ml-3`}>
                        <span
                            css={tw`font-sans font-normal text-sm text-neutral-50 whitespace-nowrap leading-tight select-none`}
                        >
                            {email}
                        </span>
                        <span
                            css={tw`font-header font-normal text-xs text-neutral-300 whitespace-nowrap leading-tight select-none`}
                        >
                            {roleName}
                        </span>
                    </div>
                </Sidebar.User>
            </Sidebar>

            <div css={tw`flex-1 overflow-x-hidden px-6 pt-6 lg:px-10 lg:pt-8 xl:px-16 xl:pt-12`}>
                <div css={tw`w-full flex flex-col mx-auto`} style={{ maxWidth: '86rem' }}>
                    <Routes>
                        <Route path="" element={<OverviewContainer />} />
                        <Route path="settings/*" element={<SettingsContainer />} />
                        <Route path="databases" element={<DatabasesContainer />} />
                        <Route path="databases/new" element={<NewDatabaseContainer />} />
                        <Route path="databases/:id" element={<DatabaseEditContainer />} />
                        <Route path="locations" element={<LocationsContainer />} />
                        <Route path="locations/:id" element={<LocationEditContainer />} />
                        <Route path="nodes" element={<NodesContainer />} />
                        <Route path="nodes/new" element={<NewNodeContainer />} />
                        <Route path="nodes/:id/*" element={<NodeRouter />} />
                        <Route path="servers" element={<ServersContainer />} />
                        <Route path="servers/new" element={<NewServerContainer />} />
                        <Route path="servers/:id/*" element={<ServerRouter />} />
                        <Route path="users" element={<UsersContainer />} />
                        <Route path="users/new" element={<NewUserContainer />} />
                        <Route path="users/:id/*" element={<UserRouter />} />
                        <Route path="roles" element={<RolesContainer />} />
                        <Route path="roles/:id" element={<RoleEditContainer />} />
                        <Route path="nests" element={<NestsContainer />} />
                        <Route path="nests/:nestId" element={<NestEditContainer />} />
                        <Route path="nests/:nestId/new" element={<NewEggContainer />} />
                        <Route path="nests/:nestId/eggs/:id/*" element={<EggRouter />} />
                        <Route path="mounts" element={<MountsContainer />} />
                        <Route path="mounts/new" element={<NewMountContainer />} />
                        <Route path="mounts/:id" element={<MountEditContainer />} />
                        <Route path="*" element={<NotFound />} />
                    </Routes>
                </div>
            </div>
        </div>
    );
}

export default AdminRouter;
