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
import { ExtensionResourceProvider, type ExtensionResourceContext } from '@/extensions/resourceContext';
import Slot from '@/extensions/Slot';
import { resourceNavigation, type ResourceTab } from '@/views/navigation';

const tabs: ResourceTab[] = [
    { id: 'about', label: 'About', to: '/panel/nodes/$id', exact: true },
    { id: 'settings', label: 'Settings', to: '/panel/nodes/$id/settings' },
    { id: 'configuration', label: 'Configuration', to: '/panel/nodes/$id/configuration' },
    { id: 'allocation', label: 'Allocation', to: '/panel/nodes/$id/allocation' },
    { id: 'tags', label: 'Tags', to: '/panel/nodes/$id/tags' },
    { id: 'servers', label: 'Servers', to: '/panel/nodes/$id/servers' },
];

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
        return <Spinner size='large' centered />;
    }

    const { attributes } = node;

    return (
        <ExtensionResourceProvider.Provider value={resourceContext}>
            <AdminContentBlock
                title={`Admin · ${attributes.name}`}
                heading={attributes.name}
                description={attributes.fqdn}
            >
                <Link
                    to='/panel/nodes'
                    className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
                >
                    <Icon icon={ArrowLeft} className='mr-2' />
                    Back to Nodes
                </Link>
                <Slot name='panel.nodes.detail.actions' data={resourceContext} />
                <SubNavigation
                    className='mb-6 rounded-sm'
                    items={resourceNavigation('admin.node', { id: String(attributes.id) }, tabs)}
                />
                <Outlet />
            </AdminContentBlock>
        </ExtensionResourceProvider.Provider>
    );
}
