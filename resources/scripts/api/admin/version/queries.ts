import { useQuery } from '@tanstack/react-query';

import { adminGetVersionInformationOptions } from '@/api/generated/@tanstack/react-query.gen';
import type { AdminGetVersionInformationResponse } from '@/api/generated';

export type AdminVersion = AdminGetVersionInformationResponse;

export const adminVersionQueryOptions = () => ({
    ...adminGetVersionInformationOptions(),
});

type AdminVersionQueryOptions = { enabled?: boolean };

export const useAdminVersion = (options?: AdminVersionQueryOptions) =>
    useQuery({ ...adminVersionQueryOptions(), ...options });
