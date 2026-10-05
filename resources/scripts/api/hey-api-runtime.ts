import type { CreateClientConfig } from './generated/client.gen';
import http from './http';

export const createClientConfig: CreateClientConfig = (config) => ({
    ...config,
    axios: http,
    baseURL: '',
    throwOnError: true,
});
