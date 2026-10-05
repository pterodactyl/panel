import { useQuery, queryOptions } from '@tanstack/react-query';

import { clientListServerPermissionsOptions } from '@/api/generated/@tanstack/react-query.gen';

export const systemPermissionsQueryOptions = queryOptions({
    ...clientListServerPermissionsOptions(),
    staleTime: Infinity,
});

export const useSystemPermissions = () => useQuery(systemPermissionsQueryOptions);
