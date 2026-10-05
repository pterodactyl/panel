// Core's preconfigured Hey API client, served to generated extension clients via the import map.
import type { Options as GeneratedOptions } from '@/api/generated/client';

export { client } from '@/api/generated/client.gen';
export { createClient, createConfig } from '@/api/generated/client';
export type {
    Client,
    ClientMeta,
    ClientOptions,
    Config,
    Options,
    RequestOptions,
    RequestResult,
} from '@/api/generated/client';
/** The request data every generated operation's options extend. */
export type RequestData = GeneratedOptions extends GeneratedOptions<infer TData> ? TData : never;
export { createClientConfig } from '@/api/hey-api-runtime';

export { default as http, httpErrorToHuman } from '@/api/http';
export { queryClient } from '@/api/queryClient';
