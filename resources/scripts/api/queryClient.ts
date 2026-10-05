import { QueryClient } from '@tanstack/react-query';
import { retryUnlessClientError } from '@/api/queryRetry';

export const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 30_000,
            refetchOnWindowFocus: false,
            retry: retryUnlessClientError(),
        },
    },
});
