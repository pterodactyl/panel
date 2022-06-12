import React, { lazy } from 'react';
import ServerConsole from '@/components/server/ServerConsole';
import DatabasesContainer from '@/components/server/databases/DatabasesContainer';
import ScheduleContainer from '@/components/server/schedules/ScheduleContainer';
import UsersContainer from '@/components/server/users/UsersContainer';
import BackupContainer from '@/components/server/backups/BackupContainer';
import NetworkContainer from '@/components/server/network/NetworkContainer';
import StartupContainer from '@/components/server/startup/StartupContainer';
import FileManagerContainer from '@/components/server/files/FileManagerContainer';
import SettingsContainer from '@/components/server/settings/SettingsContainer';

const FileEditContainer = lazy(() => import('@/components/server/files/FileEditContainer'));
const ScheduleEditContainer = lazy(() => import('@/components/server/schedules/ScheduleEditContainer'));

interface ServerRouteDefinition {
    path: string;
    permission: string | string[] | null;
    // If undefined is passed this route is still rendered into the router itself
    // but no navigation link is displayed in the sub-navigation menu.
    name: string | undefined;
    component: React.ComponentType;
    // The default for "exact" is assumed to be "true" unless you explicitly
    // pass it as false.
    exact?: boolean;
}

interface Routes {
    server: ServerRouteDefinition[];
}

export default {
    server: [
        {
            path: '/',
            permission: null,
            name: 'Console',
            component: ServerConsole,
            exact: true,
        },
        {
            path: '/files',
            permission: 'file.*',
            name: 'Files',
            component: FileManagerContainer,
        },
        {
            path: '/files/:action(edit|new)',
            permission: 'file.*',
            name: undefined,
            component: FileEditContainer,
        },
        {
            path: '/databases',
            permission: 'database.*',
            name: 'Databases',
            component: DatabasesContainer,
        },
        {
            path: '/schedules',
            permission: 'schedule.*',
            name: 'Schedules',
            component: ScheduleContainer,
        },
        {
            path: '/schedules/:id',
            permission: 'schedule.*',
            name: undefined,
            component: ScheduleEditContainer,
        },
        {
            path: '/users',
            permission: 'user.*',
            name: 'Users',
            component: UsersContainer,
        },
        {
            path: '/backups',
            permission: 'backup.*',
            name: 'Backups',
            component: BackupContainer,
        },
        {
            path: '/network',
            permission: 'allocation.*',
            name: 'Network',
            component: NetworkContainer,
        },
        {
            path: '/startup',
            permission: 'startup.*',
            name: 'Startup',
            component: StartupContainer,
        },
        {
            path: '/settings',
            permission: [ 'settings.*', 'file.sftp' ],
            name: 'Settings',
            component: SettingsContainer,
        },
    ],
} as Routes;
