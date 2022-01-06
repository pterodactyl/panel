import useSWR from 'swr';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { ServerLog } from '@/api/server/types';
import { rawDataToServerLog } from '@/api/transformers';
import { ServerContext } from '@/state/server';
import { createContext, useContext } from 'react';

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

type LogResponse = PaginatedResult<ServerLog> & { logCount: number };

export default () => {
    const { page } = useContext(Context);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    return useSWR<LogResponse>([ 'server:logs', uuid, page ], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/logs`, { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToServerLog),
            pagination: getPaginationSet(data.meta.pagination),
            logCount: data.meta.log_count,
        });
    });
};
