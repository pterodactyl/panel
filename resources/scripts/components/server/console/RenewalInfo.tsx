import React from 'react';
import { ServerContext } from '@/state/server';

export default () => {
    const renewal = ServerContext.useStoreState((state) => state.server.data!.renewal);
    return <>in {renewal} days</>;
};
