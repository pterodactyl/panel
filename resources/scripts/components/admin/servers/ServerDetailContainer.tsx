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
import ResourceExtensionTabs from '@/extensions/ResourceExtensionTabs';
import { ExtensionResourceProvider, type ExtensionResourceContext } from '@/extensions/resourceContext';
import Slot from '@/extensions/Slot';

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
        return <Spinner size={'large'} centered />;
    }

    const { attributes } = server;
    const params = { id: attributes.id };
    const isInstalled = attributes.container.installed === 1;

    return (
        <ExtensionResourceProvider.Provider value={resourceContext}>
            <AdminContentBlock
                title={`Admin · ${attributes.name}`}
                heading={attributes.name}
                description={attributes.identifier}
            >
                <div className={'mb-4 flex flex-wrap items-center gap-4'}>
                    <Link
                        to={'/panel/servers'}
                        className={'inline-flex items-center text-sm text-muted-foreground hover:text-foreground'}
                    >
                        <Icon icon={ArrowLeft} className={'mr-2'} />
                        Back to Servers
                    </Link>
                    <Link
                        to={'/server/$id'}
                        params={{ id: attributes.identifier }}
                        className={'inline-flex items-center text-sm text-muted-foreground hover:text-foreground'}
                    >
                        <Icon icon={ExternalLink} className={'mr-2'} />
                        Open Client Area
                    </Link>
                </div>
                <Slot name='panel.servers.detail.actions' data={resourceContext} />
                <SubNavigation className={'mb-6 rounded-sm'}>
                    <Link
                        data-core
                        to={'/panel/servers/$id'}
                        params={params}
                        activeOptions={{ exact: true, includeSearch: false }}
                    >
                        About
                    </Link>
                    {isInstalled && (
                        <>
                            <Link data-core to={'/panel/servers/$id/details'} params={params}>
                                Details
                            </Link>
                            <Link data-core to={'/panel/servers/$id/build'} params={params}>
                                Build
                            </Link>
                            <Link data-core to={'/panel/servers/$id/startup'} params={params}>
                                Startup
                            </Link>
                            <Link data-core to={'/panel/servers/$id/database'} params={params}>
                                Database
                            </Link>
                            <Link data-core to={'/panel/servers/$id/mounts'} params={params}>
                                Mounts
                            </Link>
                        </>
                    )}
                    <Link data-core to={'/panel/servers/$id/manage'} params={params}>
                        Manage
                    </Link>
                    <Link data-core to={'/panel/servers/$id/delete'} params={params}>
                        Delete
                    </Link>
                    <ResourceExtensionTabs parent='admin.server' basePath={`/panel/servers/${attributes.id}`} />
                </SubNavigation>
                <Outlet />
            </AdminContentBlock>
        </ExtensionResourceProvider.Provider>
    );
}
