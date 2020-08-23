import { ServerContext } from '@/state/server';
import { Server } from '@/api/server/getServer';

const useServer = (dependencies?: any[] | undefined): Server => {
    return ServerContext.useStoreState(state => state.server.data!, dependencies);
};

export default useServer;
