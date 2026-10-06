import { useMemo } from 'react';
import { Link, Outlet } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { useAdminNode } from '@/api/admin/nodes/queries';
import { nodeDetailRoute } from '@/router/routeTree';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Icon from '@/components/elements/Icon';
import SubNavigation from '@/components/elements/SubNavigation';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import ResourceExtensionTabs from '@/extensions/ResourceExtensionTabs';
import { ExtensionResourceProvider, type ExtensionResourceContext } from '@/extensions/resourceContext';
import Slot from '@/extensions/Slot';

export default function NodeDetailContainer() {
    const loadedNode = nodeDetailRoute.useLoaderData();
    const nodeId = loadedNode.attributes.id;

    const { data: node = loadedNode, error } = useAdminNode(nodeId);

    const resourceContext = useMemo<Extract<ExtensionResourceContext, { kind: 'admin.node' }>>(
        () => ({ kind: 'admin.node', resource: node }),
        [node]
    );

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!node) {
        return <Spinner size={'large'} centered />;
    }

    const { attributes } = node;
    const params = { id: attributes.id };

    return (
        <ExtensionResourceProvider.Provider value={resourceContext}>
            <AdminContentBlock
                title={`Admin · ${attributes.name}`}
                heading={attributes.name}
                description={attributes.fqdn}
            >
                <Link
                    to={'/panel/nodes'}
                    className={'inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'}
                >
                    <Icon icon={ArrowLeft} className={'mr-2'} />
                    Back to Nodes
                </Link>
                <Slot name='panel.nodes.detail.actions' data={resourceContext} />
                <SubNavigation className={'mb-6 rounded-sm'}>
                    <Link to={'/panel/nodes/$id'} params={params} activeOptions={{ exact: true, includeSearch: false }}>
                        About
                    </Link>
                    <Link to={'/panel/nodes/$id/settings'} params={params}>
                        Settings
                    </Link>
                    <Link to={'/panel/nodes/$id/configuration'} params={params}>
                        Configuration
                    </Link>
                    <Link to={'/panel/nodes/$id/allocation'} params={params}>
                        Allocation
                    </Link>
                    <Link to={'/panel/nodes/$id/servers'} params={params}>
                        Servers
                    </Link>
                    <ResourceExtensionTabs parent='admin.node' basePath={`/panel/nodes/${attributes.id}`} />
                </SubNavigation>
                <Outlet />
            </AdminContentBlock>
        </ExtensionResourceProvider.Provider>
    );
}
