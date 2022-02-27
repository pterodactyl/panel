import { ServerContext } from '@/state/server';
import useSWR from 'swr';
import http from '@/api/http';
import { Allocation, Transformers } from '@definitions/user';

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    return useSWR<Allocation[]>([ 'server:allocations', uuid ], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/network/allocations`);

        return (data.data || []).map(Transformers.toServerAllocation);
    }, { revalidateOnFocus: false, revalidateOnMount: false });
};
