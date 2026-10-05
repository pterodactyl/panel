import { defineConfig } from '@hey-api/openapi-ts';

export default defineConfig({
    input: './openapi.yaml',
    output: { path: './src/client/generated', clean: true },
    plugins: [
        '@hey-api/typescript',
        { name: '@hey-api/client-axios', baseUrl: false, throwOnError: true },
        { name: '@hey-api/sdk', client: '@hey-api/client-axios', paramsStructure: 'grouped' },
        { name: '@tanstack/react-query', queryKeys: true, queryOptions: true, mutationKeys: true, mutationOptions: true },
    ],
});
