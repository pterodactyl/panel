import useSWR from 'swr';
import http, { FractalResponseList } from '@/api/http';
import { rawDataToServerEggVariable } from '@/api/transformers';
import { ServerEggVariable } from '@/api/server/types';

interface Response {
    invocation: string;
    variables: ServerEggVariable[];
    dockerImages: string[];
}

export default (uuid: string, initialData?: Response) => useSWR([ uuid, '/startup' ], async (): Promise<Response> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/startup`);

    const variables = ((data as FractalResponseList).data || []).map(rawDataToServerEggVariable);

    return { invocation: data.meta.startup_command, variables, dockerImages: data.meta.docker_images || [] };
}, { initialData, errorRetryCount: 3 });
