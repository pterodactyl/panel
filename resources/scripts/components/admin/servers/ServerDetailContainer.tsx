import { useMemo } from 'react';
import { Link, Outlet } from '@tanstack/react-router';
import { ArrowLeft, ExternalLink } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { useAdminServer } from '@/api/admin/servers/queries';
import { serverDetailRoute } from '@/router/routeTree';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Icon from '@/components/elements/Icon';
import SubNavigation from '@/components/elements/SubNavigation';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import { ExtensionResourceProvider, type ExtensionResourceContext } from '@/extensions/resourceContext';
import Slot from '@/extensions/Slot';
import { resourceNavigation, type ResourceTab } from '@/views/navigation';

const aboutTab: ResourceTab = { id: 'about', label: 'About', to: '/panel/servers/$id', exact: true };
const installedTabs: ResourceTab[] = [
    { id: 'details', label: 'Details', to: '/panel/servers/$id/details' },
    { id: 'build', label: 'Build', to: '/panel/servers/$id/build' },
    { id: 'startup', label: 'Startup', to: '/panel/servers/$id/startup' },
    { id: 'database', label: 'Database', to: '/panel/servers/$id/database' },
    { id: 'mounts', label: 'Mounts', to: '/panel/servers/$id/mounts' },
];
const manageTabs: ResourceTab[] = [
    { id: 'manage', label: 'Manage', to: '/panel/servers/$id/manage' },
    { id: 'delete', label: 'Delete', to: '/panel/servers/$id/delete' },
];

export default function ServerDetailContainer() {
    const loadedServer = serverDetailRoute.useLoaderData();
    const serverId = loadedServer.attributes.id;

    const { data: server = loadedServer, error } = useAdminServer(serverId);

    const resourceContext = useMemo<Extract<ExtensionResourceContext, { kind: 'admin.server' }>>(
        () => ({ kind: 'admin.server', resource: server }),
        [server]
    );

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!server) {
        return <Spinner size='large' centered />;
    }

    const { attributes } = server;
    const tabs = [aboutTab, ...(attributes.container.installed === 1 ? installedTabs : []), ...manageTabs];

    return (
        <ExtensionResourceProvider.Provider value={resourceContext}>
            <AdminContentBlock
                title={`Admin · ${attributes.name}`}
                heading={attributes.name}
                description={attributes.identifier}
            >
                <div className='mb-4 flex flex-wrap items-center gap-4'>
                    <Link
                        to='/panel/servers'
                        className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground'
                    >
                        <Icon icon={ArrowLeft} className='mr-2' />
                        Back to Servers
                    </Link>
                    <Link
                        to='/server/$id'
                        params={{ id: attributes.identifier }}
                        className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground'
                    >
                        <Icon icon={ExternalLink} className='mr-2' />
                        Open Client Area
                    </Link>
                </div>
                <Slot name='panel.servers.detail.actions' data={resourceContext} />
                <SubNavigation
                    className='mb-6 rounded-sm'
                    items={resourceNavigation('admin.server', { id: String(attributes.id) }, tabs)}
                />
                <Outlet />
            </AdminContentBlock>
        </ExtensionResourceProvider.Provider>
    );
}
