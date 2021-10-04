import React from 'react';
import ServersTable from '@/components/admin/servers/ServersTable';
import { Context } from '@/components/admin/nodes/NodeRouter';

function NodeServers () {
    const node = Context.useStoreState(state => state.node);

    return (
        <ServersTable filters={{ node_id: node?.id?.toString() }}/>
    );
}

export default NodeServers;
