import { DependencyList } from 'react';
import { ServerContext } from '@/state/server';
import { Server } from '@/api/server/getServer';

const useServer = (dependencies?: DependencyList): Server => {
    return ServerContext.useStoreState(state => state.server.data!, [ dependencies ]);
};

export default useServer;
