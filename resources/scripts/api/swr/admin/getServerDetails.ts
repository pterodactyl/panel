import useSWR, { SWRResponse } from 'swr';
import http from '@/api/http';
import { rawDataToServer, Server } from '@/api/admin/servers/getServers';
import { useRouteMatch } from 'react-router-dom';
import { AxiosError } from 'axios';

export default (): SWRResponse<Server, AxiosError> => {
    const { params } = useRouteMatch<{ id: string }>();

    return useSWR(`/api/application/servers/${params.id}`, async (key) => {
        const { data } = await http.get(key, {
            params: {
                includes: [ 'allocations', 'user', 'variables' ],
            },
        });

        return rawDataToServer(data);
    }, { revalidateOnMount: false, revalidateOnFocus: false });
};
