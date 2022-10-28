import type { AxiosError } from 'axios';
import type { SWRConfiguration } from 'swr';
import useSWR from 'swr';

import http, { FractalResponseList } from '@/api/http';
import type { ServerEggVariable } from '@/api/server/types';
import { rawDataToServerEggVariable } from '@/api/transformers';

interface Response {
    invocation: string;
    variables: ServerEggVariable[];
    dockerImages: Record<string, string>;
}

export default (uuid: string, fallbackData?: Response, config?: SWRConfiguration<Response, AxiosError>) =>
    useSWR(
        [uuid, '/startup'],
        async (): Promise<Response> => {
            const { data } = await http.get(`/api/client/servers/${uuid}/startup`);

            const variables = ((data as FractalResponseList).data || []).map(rawDataToServerEggVariable);

            return {
                variables,
                invocation: data.meta.startup_command,
                dockerImages: data.meta.docker_images || {},
            };
        },
        { fallbackData, errorRetryCount: 3, ...(config ?? {}) },
    );
