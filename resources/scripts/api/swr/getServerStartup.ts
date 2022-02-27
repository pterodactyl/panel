import useSWR from 'swr';
import http, { FractalResponseList } from '@/api/http';
import { Transformers, ServerEggVariable } from '@definitions/user';

interface Response {
    invocation: string;
    variables: ServerEggVariable[];
    dockerImages: string[];
}

export default (uuid: string, initialData?: Response) => useSWR([ uuid, '/startup' ], async (): Promise<Response> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/startup`);

    const variables = ((data as FractalResponseList).data || []).map(Transformers.toServerEggVariable);

    return { invocation: data.meta.startup_command, variables, dockerImages: data.meta.docker_images || [] };
}, { fallbackData: initialData, errorRetryCount: 3 });
