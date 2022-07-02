import useSWR from 'swr';
import http from '@/api/http';
import { ServerContext } from '@/state/server';
import { Allocation } from '@/api/server/getServer';
import { rawDataToServerAllocation } from '@/api/transformers';

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<Allocation[]>(
        ['server:allocations', uuid],
        async () => {
            const { data } = await http.get(`/api/client/servers/${uuid}/network/allocations`);

            return (data.data || []).map(rawDataToServerAllocation);
        },
        { revalidateOnFocus: false, revalidateOnMount: false }
    );
};
